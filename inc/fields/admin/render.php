<?php
/**
 * Brique 2 — Rendu des contrôles de formulaire, un par type de champ.
 *
 * Chaque fonction reçoit le champ normalisé et sa valeur brute, et n'écrit que
 * le contrôle lui-même. Le libellé, l'aide et l'enveloppe sont produits par
 * `meta-box.php`, pour que tous les types se ressemblent sans effort.
 *
 * Convention de nommage des inputs : `iron[groupe][champ]`, et
 * `iron[groupe][champ][sous_clé]` pour les types composés.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_field_input_name')) {
    /**
     * @param array  $field
     * @param string $sub Sous-clé pour les types composés (ex. `url`).
     * @return string
     */
    function iron_field_input_name(array $field, $sub = '')
    {
        $name = sprintf('iron[%s][%s]', $field['group'], $field['key']);

        return '' === $sub ? $name : $name . '[' . $sub . ']';
    }
}

if (!function_exists('iron_field_input_id')) {
    /**
     * @param array  $field
     * @param string $sub
     * @return string
     */
    function iron_field_input_id(array $field, $sub = '')
    {
        $id = 'iron-' . $field['group'] . '-' . $field['key'];

        return '' === $sub ? $id : $id . '-' . $sub;
    }
}

if (!function_exists('iron_render_text_field')) {
    /**
     * @param array $field
     * @param mixed $value
     * @return void
     */
    function iron_render_text_field(array $field, $value)
    {
        printf(
            '<input type="text" class="widefat" id="%s" name="%s" value="%s">',
            esc_attr(iron_field_input_id($field)),
            esc_attr(iron_field_input_name($field)),
            esc_attr((string) $value)
        );
    }
}

if (!function_exists('iron_render_textarea_field')) {
    /**
     * @param array $field
     * @param mixed $value
     * @return void
     */
    function iron_render_textarea_field(array $field, $value)
    {
        printf(
            '<textarea class="widefat" rows="4" id="%s" name="%s">%s</textarea>',
            esc_attr(iron_field_input_id($field)),
            esc_attr(iron_field_input_name($field)),
            esc_textarea((string) $value)
        );
    }
}

if (!function_exists('iron_render_image_field')) {
    /**
     * Sélecteur d'image adossé à la médiathèque native (`wp.media`).
     *
     * @param array $field
     * @param mixed $value ID d'attachement.
     * @return void
     */
    function iron_render_image_field(array $field, $value)
    {
        $attachment_id = absint($value);
        ?>
        <div class="iron-image" data-iron-image>

            <input
                type="hidden"
                id="<?php echo esc_attr(iron_field_input_id($field)); ?>"
                name="<?php echo esc_attr(iron_field_input_name($field)); ?>"
                value="<?php echo esc_attr((string) $attachment_id); ?>"
                data-iron-image-value>

            <div class="iron-image__preview" data-iron-image-preview>
                <?php
                if ($attachment_id) {
                    // Balise produite par le core, déjà échappée.
                    echo wp_get_attachment_image($attachment_id, 'medium');
                }
                ?>
            </div>

            <p class="iron-image__actions">
                <button type="button" class="button" data-iron-image-choose>
                    <?php esc_html_e('Choisir une image', 'ironframe'); ?>
                </button>
                <button type="button" class="button-link-delete" data-iron-image-remove <?php disabled(0, $attachment_id); ?>>
                    <?php esc_html_e('Retirer', 'ironframe'); ?>
                </button>
            </p>

        </div>
        <?php
    }
}

if (!function_exists('iron_render_link_field')) {
    /**
     * @param array $field
     * @param mixed $value Tableau url / label / target.
     * @return void
     */
    function iron_render_link_field(array $field, $value)
    {
        $value = is_array($value) ? $value : [];

        $url    = isset($value['url']) ? (string) $value['url'] : '';
        $label  = isset($value['label']) ? (string) $value['label'] : '';
        $target = isset($value['target']) ? (string) $value['target'] : '_self';
        ?>
        <div class="iron-link">

            <p class="iron-link__row">
                <label for="<?php echo esc_attr(iron_field_input_id($field, 'label')); ?>">
                    <?php esc_html_e('Texte du lien', 'ironframe'); ?>
                </label>
                <input
                    type="text"
                    class="widefat"
                    id="<?php echo esc_attr(iron_field_input_id($field, 'label')); ?>"
                    name="<?php echo esc_attr(iron_field_input_name($field, 'label')); ?>"
                    value="<?php echo esc_attr($label); ?>">
            </p>

            <p class="iron-link__row">
                <label for="<?php echo esc_attr(iron_field_input_id($field, 'url')); ?>">
                    <?php esc_html_e('Adresse', 'ironframe'); ?>
                </label>
                <input
                    type="url"
                    class="widefat"
                    placeholder="https://"
                    id="<?php echo esc_attr(iron_field_input_id($field, 'url')); ?>"
                    name="<?php echo esc_attr(iron_field_input_name($field, 'url')); ?>"
                    value="<?php echo esc_attr($url); ?>">
            </p>

            <p class="iron-link__row">
                <label for="<?php echo esc_attr(iron_field_input_id($field, 'target')); ?>">
                    <?php esc_html_e('Ouverture', 'ironframe'); ?>
                </label>
                <select
                    id="<?php echo esc_attr(iron_field_input_id($field, 'target')); ?>"
                    name="<?php echo esc_attr(iron_field_input_name($field, 'target')); ?>">
                    <option value="_self" <?php selected($target, '_self'); ?>>
                        <?php esc_html_e('Dans la même fenêtre', 'ironframe'); ?>
                    </option>
                    <option value="_blank" <?php selected($target, '_blank'); ?>>
                        <?php esc_html_e('Dans un nouvel onglet', 'ironframe'); ?>
                    </option>
                </select>
            </p>

        </div>
        <?php
    }
}
