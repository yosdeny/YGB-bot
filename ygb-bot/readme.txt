=== YGB Bot ===

Contributors: ygb
Tags: chatbot, faq, support, widget, gdpr
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Chatbot de preguntas y respuestas basado en reglas (sin IA), con derivación a soporte por email/WhatsApp, RGPD y 100% local.

== Description ==

YGB Bot es un plugin de WordPress que añade un widget de chat flotante (tipo Intercom) capaz de responder preguntas frecuentes usando una base de conocimiento local con coincidencia difusa (normalización, Levenshtein, similar_text y palabras clave). No usa IA, no llama a servicios externos y no envía datos fuera del servidor.

*Base de conocimiento*
- Temas (categorías) con icono, orden y estado.
- Preguntas con variaciones (una por línea), palabras clave, respuesta con HTML básico, enlace, imagen y archivo adjunto opcionales.
- Prioridad para desempates y contador de consultas (FAQ más populares).
- Importar/exportar en JSON y CSV.

*Widget público*
- Burbuja flotante configurable: nombre del bot, avatar, color, posición (izquierda/derecha), tamaño, mensaje de bienvenida, placeholder y textos.
- Menú inicial por temas, FAQ sugeridas y botón persistente “Hablar con soporte”.
- Indicador “escribiendo…”, historial opcional en localStorage, carga diferida.
- Responsive y accesible (roles ARIA, teclado, focus visible).
- Integración mediante shortcode [ygb_bot], bloque de Gutenberg, widget clásico o la función ygb_bot_render(). Funciona con Elementor, Divi y Beaver Builder.

*Derivación a soporte (fallback)*
- Mensaje de fallback configurable y umbral de coincidencia ajustable.
- Detección de intención (“humano”, “agente”, “soporte”…) y derivación automática tras 3 mensajes sin coincidencia.
- Canales: correo electrónico (mailto con contexto), WhatsApp (wa.me con mensaje prellenado), URL de contacto y formulario interno con notificación al administrador.

*RGPD y privacidad*
- Aviso de consentimiento configurable antes de guardar conversaciones.
- Anonimización de IPs por hash.
- Opción de no registrar chats en absoluto.
- Export/borrado de registros desde el panel. Al desinstalar se elimina todo (tablas, opciones y transients).

*Rendimiento*
- Caché de la base de conocimiento en transients, invalidada al guardar.
- CSS/JS propios, sin jQuery ni dependencias externas.
- Compatible con WP Rocket, W3TC y LiteSpeed.

== Installation ==

1. Sube la carpeta `ygb-bot` al directorio `/wp-content/plugins/`.
2. Activa el plugin desde “Plugins”. Se crearán las tablas y un contenido de ejemplo.
3. Ve a **YGB Bot → Ayuda** para una guía rápida y configura Temas y Preguntas.

== Frequently Asked Questions ==

= ¿Necesita API de OpenAI o servicios externos? =
No. Todo el emparejamiento ocurre en tu servidor con reglas locales (normalización + similitud + keywords).

= ¿Dónde configuro el número de WhatsApp? =
En YGB Bot → Derivación. Usa formato internacional, por ejemplo +584121234567.

= ¿Puedo ocultar la burbuja en ciertas páginas? =
Sí: desactiva “Carga automática” en Ajustes generales e inserta el bot solo donde quieras con el shortcode [ygb_bot] o el bloque.

= ¿Cómo pruebo las respuestas sin abrir el sitio? =
Usa el panel “Probar bot” en YGB Bot → Apariencia.

= ¿Qué pasa con los datos personales? =
Puedes desactivar el registro de conversaciones, hashear IPs, borrar registros en cualquier momento y todo se elimina al desinstalar.

== Changelog ==

= 1.0.0 =
* Estreno: motor de reglas, CRUD de temas/preguntas, widget personalizable, derivación a soporte, registros, import/export, ajustes, ayuda integrada, RGPD y bloque de Gutenberg.

== Upgrade Notice ==

= 1.0.0 =
Primera versión estable del plugin.
