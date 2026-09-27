<?php
/**
 * Motor de búsqueda del bot (sin IA).
 *
 * Puntuación por coincidencia exacta, variaciones, palabras clave y
 * similitud parcial (similar_text / levenshtein), con caché en transients.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Class_Ygb_Search_Engine
 */
class Class_Ygb_Search_Engine {

	/**
	 * Clave del transient de caché.
	 */
	const CACHE_KEY = 'ygb_kb_cache';

	/**
	 * Normaliza texto: minúsculas, sin acentos ni signos, espacios colapsados.
	 *
	 * @param string $text Texto crudo.
	 * @return string
	 */
	public static function normalize( $text ) {
		$text = mb_strtolower( (string) $text, 'UTF-8' );

		if ( function_exists( 'iconv' ) ) {
			$conv = @iconv( 'UTF-8', 'US-ASCII//TRANSLIT//IGNORE', $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $conv ) {
				$text = $conv;
			}
		}
		$text = remove_accents( $text );

		// Elimina cualquier carácter que no sea letra (unicode), dígito o espacio.
		$text = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( (string) $text );
	}

	/**
	 * Divide en palabras normalizadas.
	 *
	 * @param string $text Texto.
	 * @return array<string>
	 */
	public static function words( $text ) {
		$norm = self::normalize( $text );
		return $norm ? explode( ' ', $norm ) : array();
	}

	/**
	 * Carga la base de conocimiento (temas + preguntas activas) cacheada.
	 *
	 * Una sola consulta con JOIN para evitar N+1.
	 *
	 * @return array<object> Preguntas con datos del tema.
	 */
	public static function get_knowledgebase() {
		$settings = Class_Ygb_DB::get_settings();
		if ( empty( $settings['cache_enabled'] ) ) {
			return self::query_kb();
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$kb = self::query_kb();
		set_transient( self::CACHE_KEY, $kb, HOUR_IN_SECONDS );
		return $kb;
	}

	/**
	 * Consulta la base de conocimiento (una única sentencia SQL).
	 *
	 * @return array<object>
	 */
	private static function query_kb() {
		global $wpdb;
		$t     = Class_Ygb_DB::tables();
		$rows  = $wpdb->get_results(
			"SELECT p.*, t.nombre AS tema_nombre, t.icono AS tema_icono
			 FROM {$t['preguntas']} p
			 LEFT JOIN {$t['temas']} t ON t.id = p.tema_id AND t.activo = 1
			 WHERE p.activo = 1
			 ORDER BY p.prioridad DESC, p.id ASC"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Invalida la caché de la base de conocimiento.
	 *
	 * @return void
	 */
	public static function invalidate_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Similitud entre dos cadenas (0..100) combinando similar_text y Levenshtein.
	 *
	 * @param string $a Cadena A (normalizada).
	 * @param string $b Cadena B (normalizada).
	 * @return float
	 */
	public static function similarity( $a, $b ) {
		if ( '' === $a || '' === $b ) {
			return 0.0;
		}
		if ( $a === $b ) {
			return 100.0;
		}

		similar_text( $a, $b, $percent );
		$sim = (float) $percent;

		// Levenshtein normalizado como contraste.
		$max   = max( mb_strlen( $a ), mb_strlen( $b ) );
		$lev   = levenshtein( $a, $b );
		$lev_p = $max > 0 ? ( 1 - $lev / $max ) * 100 : 0;

		// Promedio ponderado a favor de similar_text.
		return ( $sim * 0.6 ) + ( $lev_p * 0.4 );
	}

	/**
	 * Busca la mejor respuesta para un mensaje.
	 *
	 * @param string $message Mensaje del usuario.
	 * @return array {
	 *     @type int    $matched   1|0
	 *     @type object $question  Pregunta ganadora o null.
	 *     @type float  $score     Mejor score.
	 *     @type array  $suggest   Preguntas sugeridas (top 3 por debajo del umbral).
	 * }
	 */
	public static function search( $message ) {
		$threshold = (float) Class_Ygb_DB::get_setting( 'threshold', 60 );
		$user_norm = self::normalize( $message );
		$user_words = self::words( $message );

		$result = array(
			'matched'  => 0,
			'question' => null,
			'score'    => 0.0,
			'suggest'  => array(),
		);

		if ( '' === $user_norm ) {
			return $result;
		}

		$best      = null;
		$best_score = 0.0;
		$best_pri   = -1;
		$candidates = array();

		foreach ( self::get_knowledgebase() as $q ) {
			$score = 0.0;

			/* a) Coincidencia exacta con la pregunta principal. */
			if ( self::normalize( $q->pregunta ) === $user_norm ) {
				$score = 100.0;
			} else {
				/* b) Coincidencia exacta con alguna variación. */
				$vars = preg_split( '/\r\n|\r|\n/', (string) $q->variaciones );
				foreach ( (array) $vars as $v ) {
					$v = trim( $v );
					if ( '' !== $v && self::normalize( $v ) === $user_norm ) {
						$score = 98.0;
						break;
					}
				}
			}

			/* c) Palabras clave (peso alto). */
			if ( $score < 90 ) {
				$kw_matched = 0;
				$kws        = array_filter( array_map( 'trim', explode( ',', (string) $q->keywords ) ) );
				foreach ( $kws as $kw ) {
					$kw_norm = self::normalize( $kw );
					if ( '' === $kw_norm ) {
						continue;
					}
					if ( ' ' !== $kw_norm && false !== strpos( ' ' . $user_norm . ' ', ' ' . $kw_norm . ' ' ) ) {
						$kw_matched++;
					} elseif ( ctype_alnum( str_replace( ' ', '', $kw_norm ) ) && false !== strpos( $user_norm, $kw_norm ) ) {
						$kw_matched += 0.75; // Coincidencia dentro de palabra.
					}
				}
				if ( $kw_matched > 0 ) {
					$kw_score = min( 95.0, 70.0 + ( $kw_matched * 10 ) );
					$score    = max( $score, $kw_score );
				}
			}

			/* d) Coincidencia parcial (similitud) con pregunta y variaciones. */
			if ( $score < $threshold ) {
				$sims = array( self::similarity( self::normalize( $q->pregunta ), $user_norm ) );
				$vars = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $q->variaciones ) ) );
				foreach ( array_slice( $vars, 0, 20 ) as $v ) {
					$sims[] = self::similarity( self::normalize( $v ), $user_norm );
				}
				// Cobertura de palabras del usuario sobre la pregunta.
				$q_words = self::words( $q->pregunta );
				if ( $q_words && $user_words ) {
					$inter  = count( array_intersect( $user_words, $q_words ) );
					$cov    = $inter / max( 1, count( $user_words ) ) * 100;
					$sims[] = $cov * 0.9;
				}
				$score = max( $score, max( $sims ) );
			}

			if ( $score >= $threshold ) {
				$pri = (int) $q->prioridad;
				if ( $score > $best_score || ( abs( $score - $best_score ) < 0.001 && $pri > $best_pri ) ) {
					$best       = $q;
					$best_score = $score;
					$best_pri   = $pri;
				}
			} elseif ( $score >= 35 ) {
				$candidates[] = (object) array(
					'id'       => (int) $q->id,
					'pregunta' => $q->pregunta,
					'score'    => $score,
					'prioridad' => (int) $q->prioridad,
				);
			}
		}

		if ( $best ) {
			$result['matched']  = 1;
			$result['question'] = $best;
			$result['score']    = round( $best_score, 2 );
		}

		// Sugerencias "quizás quisiste decir".
		if ( $candidates ) {
			usort(
				$candidates,
				function ( $a, $b ) {
					if ( $a->score == $b->score ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						return $b->prioridad <=> $a->prioridad;
					}
					return $b->score <=> $a->score;
				}
			);
			$result['suggest'] = array_slice( $candidates, 0, 3 );
		}

		/**
		 * Permite alterar el resultado de la búsqueda.
		 *
		 * @param array  $result  Resultado.
		 * @param string $message Mensaje original.
		 */
		return apply_filters( 'ygb_search_result', $result, $message );
	}

	/**
	 * Formatea la respuesta de una pregunta como HTML seguro.
	 *
	 * @param object $q Fila de pregunta.
	 * @return string HTML.
	 */
	public static function format_answer( $q ) {
		$html = wpautop( wp_kses_post( $q->respuesta ) );

		if ( ! empty( $q->enlace ) ) {
			$html .= sprintf(
				'<p class="ygb-attachment"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s ↗</a></p>',
				esc_url( $q->enlace ),
				esc_html__( 'Más información', 'ygb-bot' )
			);
		}
		if ( ! empty( $q->imagen ) ) {
			$html .= sprintf(
				'<p class="ygb-attachment"><img src="%1$s" alt="" loading="lazy" style="max-width:100%%;height:auto;border-radius:8px" /></p>',
				esc_url( $q->imagen )
			);
		}
		if ( ! empty( $q->archivo ) ) {
			$html .= sprintf(
				'<p class="ygb-attachment"><a href="%1$s" target="_blank" rel="noopener noreferrer">📎 %2$s</a></p>',
				esc_url( $q->archivo ),
				esc_html__( 'Descargar archivo', 'ygb-bot' )
			);
		}

		return $html;
	}
}
