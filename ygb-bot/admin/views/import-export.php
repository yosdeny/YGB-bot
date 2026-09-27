<?php
/**
 * Vista: Importar / Exportar (JSON y CSV).
 *
 * @package YGB_Bot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ygb-wrap">
	<h1><?php esc_html_e( 'Importar / Exportar', 'ygb-bot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Copia de seguridad y migración de la base de conocimiento (temas + preguntas).', 'ygb-bot' ); ?></p>

	<div class="ygb-columns">
		<div class="ygb-col">
			<h2><?php esc_html_e( 'Exportar', 'ygb-bot' ); ?></h2>
			<p><?php esc_html_e( 'Descarga todos los temas y preguntas (incluidos los inactivos).', 'ygb-bot' ); ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( 'ygb_manage' ); ?>
				<input type="hidden" name="ygb_action" value="export" />
				<p><button type="submit" name="formato" value="json" class="button button-primary"><?php esc_html_e( '⬇ Exportar JSON (recomendado)', 'ygb-bot' ); ?></button></p>
				<p><button type="submit" name="formato" value="csv" class="button"><?php esc_html_e( '⬇ Exportar CSV', 'ygb-bot' ); ?></button></p>
			</form>
			<p class="description"><?php esc_html_e( 'El JSON preserva variaciones, keywords y adjuntos; es el formato válido para importar. El CSV es solo lectura/análisis.', 'ygb-bot' ); ?></p>
		</div>

		<div class="ygb-col">
			<h2><?php esc_html_e( 'Importar', 'ygb-bot' ); ?></h2>
			<form method="post" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ygb_manage' ); ?>
				<input type="hidden" name="ygb_action" value="import" />
				<p>
					<label for="ygb-import-file"><strong><?php esc_html_e( 'Archivo JSON exportado por YGB Bot:', 'ygb-bot' ); ?></strong></label><br />
					<input type="file" id="ygb-import-file" name="ygb_import" accept=".json,application/json" required />
				</p>
				<p class="description"><?php esc_html_e( 'La importación AÑADE los elementos como nuevos (no borra los existentes). Los IDs de tema se remapean automáticamente.', 'ygb-bot' ); ?></p>
				<?php submit_button( __( 'Importar', 'ygb-bot' ) ); ?>
			</form>
		</div>
	</div>

	<hr />
	<h2><?php esc_html_e( 'Formato del JSON', 'ygb-bot' ); ?></h2>
	<pre class="ygb-code">{
  "version": "1.0.0",
  "temas": [
    { "id": 1, "nombre": "Zelle", "descripcion": "Pagos Zelle", "icono": "💸", "orden": 0, "activo": 1 }
  ],
  "preguntas": [
    {
      "id": 1, "tema_id": 1,
      "pregunta": "¿Cómo envío dinero con Zelle?",
      "variaciones": "enviar zelle\npagar con zelle",
      "keywords": "zelle, enviar, pago",
      "respuesta": "<p>Abre tu app bancaria…</p>",
      "enlace": "", "imagen": "", "archivo": "",
      "prioridad": 0, "activo": 1
    }
  ]
}</pre>
</div>
