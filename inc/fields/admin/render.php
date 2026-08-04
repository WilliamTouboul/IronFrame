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
        // Un sous-champ de répétable arrive avec son nom déjà calculé, index
        // de ligne compris. C'est ce qui permet aux contrôles de chaque type
        // de fonctionner à l'identique dedans et dehors.
        $name = isset($field['input_name'])
            ? $field['input_name']
            : sprintf('iron[%s][%s]', $field['group'], $field['key']);

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
        $id = isset($field['input_id'])
            ? $field['input_id']
            : 'iron-' . $field['group'] . '-' . $field['key'];

        return '' === $sub ? $id : $id . '-' . $sub;
    }
}

if (!function_exists('iron_render_field_label')) {
    /**
     * Libellé d'un champ, avec son marqueur d'obligation.
     *
     * Partagé par les meta boxes, l'écran des réglages et les lignes de
     * répétable, pour que les trois se ressemblent sans effort.
     *
     * Un type composé comme l'image ou le lien ne cible aucun contrôle précis :
     * son libellé n'est donc pas une balise `label`, qui pointerait dans le
     * vide pour un lecteur d'écran.
     *
     * @param array $field
     * @return void
     */
    function iron_render_field_label(array $field)
    {
        $type      = iron_field_type($field['type']);
        $label_for = !$type || !isset($type['label_for']) || false !== $type['label_for'];
        $required  = !empty($field['required']);

        $tag        = $label_for ? 'label' : 'span';
        $attributes = $label_for
            ? sprintf(' for="%s"', esc_attr(iron_field_input_id($field)))
            : '';

        printf(
            '<%1$s class="iron-field__label"%2$s>%3$s%4$s</%1$s>',
            $tag,
            $attributes,
            esc_html($field['label']),
            $required
                ? ' <span class="iron-field__required" title="' . esc_attr__('Champ obligatoire', 'ironframe') . '">*</span>'
                : ''
        );
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

if (!function_exists('iron_render_select_field')) {
    /**
     * Liste déroulante.
     *
     * Une option vide est toujours proposée en tête : sans elle, un champ
     * jamais touché prendrait silencieusement la première valeur de la liste,
     * et le client n'aurait aucun moyen de revenir en arrière. Si le champ est
     * obligatoire, la validation refusera ce choix vide.
     *
     * @param array $field
     * @param mixed $value
     * @return void
     */
    function iron_render_select_field(array $field, $value)
    {
        $value   = (string) $value;
        $options = isset($field['options']) ? (array) $field['options'] : [];
        ?>
        <select id="<?php echo esc_attr(iron_field_input_id($field)); ?>"
                name="<?php echo esc_attr(iron_field_input_name($field)); ?>">

            <option value="">
                <?php echo esc_html('— ' . __('Aucun choix', 'ironframe') . ' —'); ?>
            </option>

            <?php foreach ($options as $option_value => $option_label) : ?>
                <option value="<?php echo esc_attr((string) $option_value); ?>"
                    <?php selected($value, (string) $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>

        </select>
        <?php
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

if (!function_exists('iron_render_repeater_field')) {
    /**
     * Liste répétable : les lignes existantes, plus un gabarit inerte que le
     * JavaScript clone pour en ajouter une.
     *
     * Les index de ligne n'ont pas besoin d'être contigus ni ordonnés : la
     * sauvegarde réindexe d'après l'ordre de soumission, qui est l'ordre du
     * DOM. C'est ce qui permet de supprimer et de déplacer une ligne sans
     * réécrire un seul attribut `name` en JavaScript.
     *
     * @param array $field
     * @param mixed $value Liste de lignes.
     * @return void
     */
    function iron_render_repeater_field(array $field, $value)
    {
        $rows = is_array($value) ? array_values($value) : [];
        $max  = isset($field['max']) ? (int) $field['max'] : 0;
        $min  = isset($field['min']) ? (int) $field['min'] : 0;
        ?>
        <div class="iron-repeater"
             data-iron-repeater
             data-iron-max="<?php echo esc_attr((string) $max); ?>"
             data-iron-min="<?php echo esc_attr((string) $min); ?>"
             data-iron-next="<?php echo esc_attr((string) count($rows)); ?>">

            <div class="iron-repeater__rows" data-iron-repeater-rows>
                <?php foreach ($rows as $index => $row) : ?>
                    <?php iron_render_repeater_row($field, $index, $row); ?>
                <?php endforeach; ?>
            </div>

            <p class="iron-repeater__empty" data-iron-repeater-empty>
                <?php esc_html_e('Aucune ligne pour le moment.', 'ironframe'); ?>
            </p>

            <p class="iron-repeater__actions">
                <button type="button" class="button button-secondary" data-iron-repeater-add>
                    <?php echo esc_html($field['label_add']); ?>
                </button>
            </p>

            <?php // Contenu inerte : rien à l'intérieur n'est soumis. ?>
            <template data-iron-repeater-template>
                <?php iron_render_repeater_row($field, '__INDEX__', []); ?>
            </template>

        </div>
        <?php
    }
}

if (!function_exists('iron_render_repeater_row')) {
    /**
     * Une ligne de répétable.
     *
     * @param array      $field
     * @param int|string $index Index de ligne, ou `__INDEX__` pour le gabarit.
     * @param array      $row   Valeurs de la ligne.
     * @return void
     */
    function iron_render_repeater_row(array $field, $index, $row)
    {
        $row = is_array($row) ? $row : [];
        ?>
        <div class="iron-repeater__row" data-iron-repeater-row>

            <div class="iron-repeater__head">
                <span class="iron-repeater__number"><?php echo esc_html($field['label_row']); ?></span>

                <span class="iron-repeater__buttons">
                    <button type="button" class="button-link iron-repeater__move" data-iron-repeater-up
                            aria-label="<?php esc_attr_e('Monter cette ligne', 'ironframe'); ?>">&uarr;</button>
                    <button type="button" class="button-link iron-repeater__move" data-iron-repeater-down
                            aria-label="<?php esc_attr_e('Descendre cette ligne', 'ironframe'); ?>">&darr;</button>
                    <button type="button" class="button-link-delete" data-iron-repeater-remove>
                        <?php esc_html_e('Supprimer', 'ironframe'); ?>
                    </button>
                </span>
            </div>

            <div class="iron-repeater__fields iron-fields">
                <?php
                foreach ($field['fields'] as $key => $sub_field) {

                    $sub_type = iron_field_type($sub_field['type']);

                    if (!$sub_type || !is_callable($sub_type['render'])) {
                        continue;
                    }

                    $sub_field['input_name'] = sprintf(
                        'iron[%s][%s][%s][%s]',
                        $field['group'],
                        $field['key'],
                        $index,
                        $key
                    );

                    $sub_field['input_id'] = sprintf(
                        'iron-%s-%s-%s-%s',
                        $field['group'],
                        $field['key'],
                        $index,
                        $key
                    );

                    $sub_value = array_key_exists($key, $row) ? $row[$key] : $sub_field['default'];
                    ?>
                    <div class="iron-field iron-field--<?php echo esc_attr($sub_field['type']); ?>">

                        <?php iron_render_field_label($sub_field); ?>

                        <div class="iron-field__control">
                            <?php call_user_func($sub_type['render'], $sub_field, $sub_value); ?>
                        </div>

                        <?php if ('' !== $sub_field['desc']) : ?>
                            <p class="iron-field__desc description"><?php echo esc_html($sub_field['desc']); ?></p>
                        <?php endif; ?>

                    </div>
                    <?php
                }
                ?>
            </div>

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
