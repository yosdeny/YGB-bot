import re

p = '/workspace/ygb-bot/admin/views/apariencia.php'
s = open(p, encoding='utf-8').read()

# Localizar la fila del avatar mediante regex (tolerante a tabulaciones).
row_re = re.compile(
    r'[ \t]*<tr>\n'
    r'[ \t]*<th scope="row"><label for="ygb-avatar">.*?</th>\n'
    r'[ \t]*<td>\n'
    r'.*?'
    r'[ \t]*</td>\n'
    r'[ \t]*</tr>',
    re.S,
)
m = row_re.search(s)
assert m, 'avatar row not found'
indent = re.match(r'[ \t]*', m.group(0)).group(0)
print('FOUND ROW:')
print(m.group(0))

new_row = indent + """<tr>
{i}\t<th scope="row"><label for="ygb-avatar"><?php esc_html_e( 'Avatar (emoji o imagen)', 'ygb-bot' ); ?></label></th>
{i}\t<td>
{i}\t\t<fieldset class="ygb-avatar-field">
{i}\t\t\t<label class="ygb-avatar-choice">
{i}\t\t\t\t<input type="radio" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar_type]" value="emoji" <?php checked( ! preg_match( '#^https?://#i', $s['avatar'] ) ); ?> />
{i}\t\t\t\t<?php esc_html_e( 'Emoji', 'ygb-bot' ); ?>
{i}\t\t\t</label>
{i}\t\t\t<label class="ygb-avatar-choice">
{i}\t\t\t\t<input type="radio" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar_type]" value="image" <?php checked( (bool) preg_match( '#^https?://#i', $s['avatar'] ) ); ?> />
{i}\t\t\t\t<?php esc_html_e( 'Imagen (URL)', 'ygb-bot' ); ?>
{i}\t\t\t</label>
{i}\t\t\t<div class="ygb-avatar-emoji"<?php echo preg_match( '#^https?://#i', $s['avatar'] ) ? ' hidden' : ''; ?>>
{i}\t\t\t\t<input id="ygb-avatar-emoji" type="text" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar_emoji]" class="small-text code" maxlength="16" placeholder="\U0001F5A5\uFE0F" value="<?php echo esc_attr( preg_match( '#^https?://#i', $s['avatar'] ) ? '' : $s['avatar'] ); ?>" />
{i}\t\t\t\t<p class="description"><?php esc_html_e( 'Ej.: \U0001F5A5\uFE0F, \U0001F4AC, \U0001F916…', 'ygb-bot' ); ?></p>
{i}\t\t\t</div>
{i}\t\t\t<div class="ygb-avatar-image"<?php echo preg_match( '#^https?://#i', $s['avatar'] ) ? '' : ' hidden'; ?>>
{i}\t\t\t\t<input id="ygb-avatar-url" type="url" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar_url]" class="large-text code ygb-media-input" maxlength="2048" placeholder="https://…/avatar.png" value="<?php echo esc_attr( preg_match( '#^https?://#i', $s['avatar'] ) ? $s['avatar'] : '' ); ?>" />
{i}\t\t\t\t<p class="description" style="margin-top:6px;">
{i}\t\t\t\t\t<button type="button" class="button ygb-media-picker"><?php esc_html_e( 'Biblioteca de medios', 'ygb-bot' ); ?></button>
{i}\t\t\t\t\t<button type="button" class="button-link ygb-avatar-clear"><?php esc_html_e( 'Quitar imagen', 'ygb-bot' ); ?></button>
{i}\t\t\t\t</p>
{i}\t\t\t\t<img id="ygb-avatar-thumb" class="ygb-avatar-thumb" alt="" src="<?php echo esc_url( preg_match( '#^https?://#i', $s['avatar'] ) ? $s['avatar'] : '' ); ?>"<?php echo preg_match( '#^https?://#i', $s['avatar'] ) ? '' : ' hidden'; ?> />
{i}\t\t\t</div>
{i}\t\t\t<!-- Valor final que se guarda: el JS sincroniza aquí el emoji o la URL. -->
{i}\t\t\t<input id="ygb-avatar" type="hidden" name="<?php echo esc_attr( Class_Ygb_DB::OPTION_KEY ); ?>[avatar]" value="<?php echo esc_attr( $s['avatar'] ); ?>" />
{i}\t\t</fieldset>
{i}\t\t<p class="description"><?php esc_html_e( 'Puedes usar un emoji o una imagen: selecciónala desde la Biblioteca de medios o pega su URL completa (no se corta).', 'ygb-bot' ); ?></p>
{i}\t</td>
{i}</tr>""".format(i=indent)

s = s[:m.start()] + new_row + s[m.end():]

# Vista previa: mostrar <img> si es URL.
prev_re = re.compile(r'([ \t]*)<span class="ygb-preview-avatar"><\?php echo esc_html\( mb_substr\( \$s\[\'avatar\'\], 0, 2 \) \); \?>\</span>')
mp = prev_re.search(s)
assert mp, 'preview avatar not found'
pi = mp.group(1)
new_prev = (
    pi + "<?php if ( preg_match( '#^https?://#i', $s['avatar'] ) ) : ?>" + '\n'
    + pi + '\t<img class="ygb-preview-avatar ygb-preview-avatar-img" src="<?php echo esc_url( $s[\'avatar\'] ); ?>" alt="" />' + '\n'
    + pi + '<?php else : ?>' + '\n'
    + pi + '<span class="ygb-preview-avatar"><?php echo esc_html( mb_substr( $s[\'avatar\'], 0, 2 ) ); ?></span>' + '\n'
    + pi + '<?php endif; ?>'
)
s = s[:mp.start()] + new_prev + s[mp.end():]

open(p, 'w', encoding='utf-8').write(s)
print('OK apariencia.php')
