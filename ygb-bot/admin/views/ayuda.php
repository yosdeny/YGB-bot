<?php
/**
 * Vista: Ayuda / Documentación integrada.
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ygb-wrap ygb-help">
	<h1><?php esc_html_e( 'Ayuda y documentación', 'ygb-bot' ); ?></h1>

	<div class="ygb-columns">
		<div class="ygb-col">
			<h2><?php esc_html_e( 'Primeros pasos', 'ygb-bot' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Crea tus Temas (pestaña Temas): agrupan las preguntas y forman el menú inicial del chat.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Crea Preguntas con su respuesta, variaciones (una por línea) y palabras clave (separadas por comas).', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Ajusta la Apariencia: color, posición, avatar, textos y vista previa en vivo.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Configura la Derivación: correo de soporte, número de WhatsApp y mensaje de fallback.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Pruébalo con el botón “Probar bot” (panel derecho en Apariencia) o visita tu sitio: la burbuja aparece abajo a la derecha.', 'ygb-bot' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Cómo decide el bot (motor sin IA)', 'ygb-bot' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Normaliza el texto: minúsculas, sin acentos, sin signos de puntuación, espacios colapsados.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Coincidencia exacta con la pregunta principal (score 100).', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Coincidencia exacta con una variación (score ~95).', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Palabras clave presentes en el mensaje (peso alto).', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Similitud parcial con similar_text + Levenshtein.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Si el mejor score ≥ umbral (60% por defecto) → responde. Empates → mayor prioridad. Si nada supera el umbral → fallback y derivación.', 'ygb-bot' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Derivación a soporte', 'ygb-bot' ); ?></h2>
			<ul class="ul-square">
				<li><?php esc_html_e( 'Palabras de intención (“humano”, “agente”, “soporte”, “operador”… ) abren la derivación directamente.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Tras 3 mensajes sin coincidencia se ofrece ayuda humana automáticamente.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Canales: email (mailto con la conversación), WhatsApp (wa.me con mensaje prellenado), URL de contacto y formulario interno (ticket + aviso al admin).', 'ygb-bot' ); ?></li>
			</ul>
		</div>

		<div class="ygb-col">
			<h2><?php esc_html_e( 'Integración en el sitio', 'ygb-bot' ); ?></h2>
			<p><?php esc_html_e( 'Shortcode (funciona en Elementor, Divi, Beaver Builder, etc.):', 'ygb-bot' ); ?></p>
			<pre class="ygb-code">[ygb_bot]
[ygb_bot posicion="inline"]</pre>
			<p><?php esc_html_e( 'Bloque de Gutenberg: busca “YGB Bot” en el editor de entradas/páginas.', 'ygb-bot' ); ?></p>
			<p><?php esc_html_e( 'Widget clásico: Aspectos → Widgets → “YGB Bot”.', 'ygb-bot' ); ?></p>
			<p><?php esc_html_e( 'En código de tema (PHP):', 'ygb-bot' ); ?></p>
			<pre class="ygb-code">&lt;?php if ( function_exists( 'ygb_bot_render' ) ) { ygb_bot_render(); } ?&gt;</pre>

			<h2><?php esc_html_e( 'RGPD y privacidad', 'ygb-bot' ); ?></h2>
			<ul class="ul-square">
				<li><?php esc_html_e( 'El widget pide consentimiento antes de guardar la conversación (configurable).', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Las IPs se guardan hasheadas si “Anonimizar IPs” está activo.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Puedes desactivar por completo el registro de chats (modo privado) o borrar/exportar los datos desde Registros.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Al desinstalar, uninstall.php elimina tablas, opciones y transients.', 'ygb-bot' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'Rendimiento', 'ygb-bot' ); ?></h2>
			<ul class="ul-square">
				<li><?php esc_html_e( 'La base de conocimiento se cachea en transients y se invalida al guardar.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'JS/CSS propios, sin jQuery ni dependencias externas; carga diferida activable.', 'ygb-bot' ); ?></li>
				<li><?php esc_html_e( 'Compatible con WP Rocket / W3TC / LiteSpeed: el contenido dinámico se carga por AJAX, la página puede cachearse.', 'ygb-bot' ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'Solución de problemas', 'ygb-bot' ); ?></h2>
			<ul class="ul-square">
				<li><strong><?php esc_html_e( 'No veo la burbuja:', 'ygb-bot' ); ?></strong> <?php esc_html_e( 'revisa Ajustes generales → Bot activo y Carga automática, o usa el shortcode.', 'ygb-bot' ); ?></li>
				<li><strong><?php esc_html_e( 'Responde mal:', 'ygb-bot' ); ?></strong> <?php esc_html_e( 'baja el umbral o añade variaciones/keywords; mira los Fallbacks en Registros para saber qué falta.', 'ygb-bot' ); ?></li>
				<li><strong><?php esc_html_e( 'WhatsApp no abre:', 'ygb-bot' ); ?></strong> <?php esc_html_e( 'el número debe incluir código de país, ej. +584121234567.', 'ygb-bot' ); ?></li>
			</ul>

			<hr />
			<p><strong>YGB Bot <?php echo esc_html( YGB_BOT_VERSION ); ?></strong> — <?php esc_html_e( 'Licencia GPL-2.0-or-later · Hecho por YGB', 'ygb-bot' ); ?></p>
		</div>
	</div>
</div>
