<?php
/**
 * Validation des champs obligatoires.
 *
 * Deux règles, selon ce qui est en jeu :
 *
 * 1. Sur une page **déjà publiée**, un champ obligatoire vidé est refusé et
 *    l'ancienne valeur est conservée. La page reste en ligne. Dépublier
 *    reviendrait à mettre le site du client hors service parce qu'il a effacé
 *    un titre par mégarde — le remède serait pire que le mal.
 *
 * 2. Sur une page **pas encore publiée**, le passage en publié est refusé tant
 *    qu'un champ obligatoire est vide. Rien n'est en ligne, donc rien à casser.
 *
 * Dans les deux cas, un message nomme les champs fautifs. Une correction
 * silencieuse serait pire qu'une erreur bruyante.
 *
 * La validation est faite **côté serveur**. L'attribut HTML `required` du
 * navigateur paraît plus simple, mais si la meta box est repliée le champ est
 * masqué, le navigateur ne peut pas y placer le focus, et il bloque l'envoi du
 * formulaire sans afficher le moindre message.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_collect_required_errors')) {
    /**
     * Liste les champs obligatoires laissés vides dans une soumission.
     *
     * Les champs appartenant à une section désactivée sont ignorés : on
     * n'exige pas de remplir ce qui ne sera pas affiché.
     *
     * @param array $schema    Schéma normalisé.
     * @param array $submitted Valeurs soumises, déjà déslashées.
     * @param array $toggles   État des interrupteurs soumis, par groupe.
     * @return array<string, string> Chemin du champ => libellé complet.
     */
    function iron_collect_required_errors(array $schema, array $submitted, array $toggles = [])
    {
        $errors = [];

        foreach ($schema as $group_key => $group) {

            if (!empty($group['toggle']) && array_key_exists($group_key, $toggles) && !$toggles[$group_key]) {
                continue;
            }

            foreach ($group['fields'] as $field_key => $field) {

                if (empty($field['required'])) {
                    continue;
                }

                // Un champ absent de la soumission n'est pas vidé : sa meta box
                // pouvait être masquée. On ne le déclare donc pas fautif.
                if (!isset($submitted[$group_key]) || !array_key_exists($field_key, (array) $submitted[$group_key])) {
                    continue;
                }

                $value = iron_sanitize_field_value($submitted[$group_key][$field_key], $field);

                if (!iron_value_is_filled($value, $field)) {
                    $errors[$field['path']] = $group['label'] . ' → ' . $field['label'];
                }
            }
        }

        return $errors;
    }
}

if (!function_exists('iron_store_errors')) {
    /**
     * Met les erreurs de côté pour l'écran suivant.
     *
     * Une redirection sépare l'enregistrement de l'affichage : il faut donc
     * transporter le message. Le transitoire est nominatif, pour qu'un autre
     * utilisateur ne récupère pas le message destiné à celui-ci.
     *
     * @param string $context Identifiant d'écran, par exemple un ID de page.
     * @param array  $errors
     * @return void
     */
    function iron_store_errors($context, array $errors)
    {
        if (!$errors) {
            return;
        }

        set_transient(_iron_errors_transient_key($context), $errors, 120);
    }
}

if (!function_exists('iron_take_errors')) {
    /**
     * Récupère les erreurs et les efface : un message ne doit s'afficher
     * qu'une fois.
     *
     * @param string $context
     * @return array
     */
    function iron_take_errors($context)
    {
        $key    = _iron_errors_transient_key($context);
        $errors = get_transient($key);

        if (false === $errors) {
            return [];
        }

        delete_transient($key);

        return is_array($errors) ? $errors : [];
    }
}

if (!function_exists('_iron_errors_transient_key')) {
    /**
     * @param string $context
     * @return string
     */
    function _iron_errors_transient_key($context)
    {
        return 'iron_errors_' . get_current_user_id() . '_' . md5((string) $context);
    }
}

if (!function_exists('iron_render_error_notice')) {
    /**
     * Affiche le message d'erreur, s'il y en a un.
     *
     * @param array  $errors
     * @param string $intro
     * @return void
     */
    function iron_render_error_notice(array $errors, $intro)
    {
        if (!$errors) {
            return;
        }
        ?>
        <div class="notice notice-error">
            <p><strong><?php echo esc_html($intro); ?></strong></p>
            <ul class="iron-error-list">
                <?php foreach ($errors as $label) : ?>
                    <li><?php echo esc_html($label); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}

/* -------------------------------------------------------------------------- */
/* Blocage de la publication                                                  */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_block_publish_when_incomplete')) {
    /**
     * Empêche une page de passer en publié tant qu'un champ obligatoire est
     * vide.
     *
     * Ne s'applique **jamais** à une page déjà publiée : celle-là est protégée
     * autrement, en refusant la valeur vide plutôt qu'en la retirant du site.
     *
     * @param array $data    Données préparées pour la base.
     * @param array $postarr Données soumises.
     * @return array
     */
    function iron_block_publish_when_incomplete($data, $postarr)
    {
        if ('page' !== $data['post_type'] || 'publish' !== $data['post_status']) {
            return $data;
        }

        $post_id = isset($postarr['ID']) ? absint($postarr['ID']) : 0;

        if (!$post_id || !_iron_submission_is_ours($post_id)) {
            return $data;
        }

        $existing = get_post($post_id);

        // Déjà en ligne : on ne la retire pas.
        if ($existing && 'publish' === $existing->post_status) {
            return $data;
        }

        $errors = iron_collect_required_errors(
            iron_get_post_schema($post_id),
            _iron_submitted_values(),
            _iron_submitted_toggles()
        );

        if (!$errors) {
            return $data;
        }

        iron_store_errors($post_id, $errors);

        $data['post_status'] = 'draft';

        return $data;
    }
}
add_filter('wp_insert_post_data', 'iron_block_publish_when_incomplete', 20, 2);

if (!function_exists('_iron_submission_is_ours')) {
    /**
     * Le formulaire soumis est-il bien le nôtre, et légitime ?
     *
     * @param int $post_id
     * @return bool
     */
    function _iron_submission_is_ours($post_id)
    {
        if (!isset($_POST['iron_fields_nonce'])) {
            return false;
        }

        $nonce = sanitize_key(wp_unslash($_POST['iron_fields_nonce']));

        return (bool) wp_verify_nonce($nonce, 'iron_save_fields_' . $post_id);
    }
}

if (!function_exists('_iron_submitted_values')) {
    /**
     * @return array
     */
    function _iron_submitted_values()
    {
        return isset($_POST['iron']) && is_array($_POST['iron'])
            ? wp_unslash($_POST['iron'])
            : [];
    }
}

if (!function_exists('_iron_submitted_toggles')) {
    /**
     * État des interrupteurs de section soumis.
     *
     * Une case décochée n'est pas transmise par le navigateur. Un champ caché
     * accompagne donc chaque interrupteur pour signaler qu'il était bien à
     * l'écran : sans lui, impossible de distinguer « le client a décoché » de
     * « la meta box n'était pas affichée ».
     *
     * @return array<string, bool>
     */
    function _iron_submitted_toggles()
    {
        if (!isset($_POST['iron_toggle_shown']) || !is_array($_POST['iron_toggle_shown'])) {
            return [];
        }

        $checked = isset($_POST['iron_toggle']) && is_array($_POST['iron_toggle'])
            ? $_POST['iron_toggle']
            : [];

        $toggles = [];

        foreach (array_keys($_POST['iron_toggle_shown']) as $group) {
            $group = sanitize_key($group);

            if ('' !== $group) {
                $toggles[$group] = isset($checked[$group]);
            }
        }

        return $toggles;
    }
}

/* -------------------------------------------------------------------------- */
/* Affichage du message sur l'écran d'édition                                 */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_page_error_notice')) {
    /**
     * @return void
     */
    function iron_page_error_notice()
    {
        $screen = get_current_screen();

        if (!$screen || 'post' !== $screen->base || 'page' !== $screen->post_type) {
            return;
        }

        $post = get_post();

        if (!$post) {
            return;
        }

        iron_render_error_notice(
            iron_take_errors($post->ID),
            __('Ces champs sont obligatoires et n\'ont pas été enregistrés vides. Leur valeur précédente a été conservée.', 'ironframe')
        );
    }
}
add_action('admin_notices', 'iron_page_error_notice');
