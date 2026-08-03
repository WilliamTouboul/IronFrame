/**
 * Ironframe — sélecteur d'image des meta boxes.
 *
 * S'appuie sur wp.media, fourni par le core via wp_enqueue_media().
 * Aucune dépendance externe, aucune étape de build.
 */
(function () {
    'use strict';

    if (typeof wp === 'undefined' || !wp.media) {
        return;
    }

    var l10n = window.ironFieldsL10n || {};

    /**
     * Une frame par champ, conservée pour retrouver la sélection courante
     * quand le client rouvre la médiathèque.
     */
    var frames = new WeakMap();

    function getParts(container) {
        return {
            input: container.querySelector('[data-iron-image-value]'),
            preview: container.querySelector('[data-iron-image-preview]'),
            remove: container.querySelector('[data-iron-image-remove]')
        };
    }

    function setValue(container, attachment) {
        var parts = getParts(container);

        if (!parts.input) {
            return;
        }

        if (!attachment) {
            parts.input.value = '';
            parts.preview.innerHTML = '';

            if (parts.remove) {
                parts.remove.disabled = true;
            }

            return;
        }

        var size = attachment.sizes && attachment.sizes.medium
            ? attachment.sizes.medium
            : attachment;

        var img = document.createElement('img');
        img.src = size.url;
        img.alt = attachment.alt || '';

        parts.input.value = attachment.id;
        parts.preview.innerHTML = '';
        parts.preview.appendChild(img);

        if (parts.remove) {
            parts.remove.disabled = false;
        }
    }

    function openFrame(container) {
        var frame = frames.get(container);

        if (!frame) {
            frame = wp.media({
                title: l10n.frameTitle || 'Choisir une image',
                button: { text: l10n.frameButton || 'Utiliser cette image' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').first();
                setValue(container, selection ? selection.toJSON() : null);
            });

            frames.set(container, frame);
        }

        frame.open();
    }

    document.addEventListener('click', function (event) {
        var choose = event.target.closest('[data-iron-image-choose]');

        if (choose) {
            event.preventDefault();
            openFrame(choose.closest('[data-iron-image]'));
            return;
        }

        var remove = event.target.closest('[data-iron-image-remove]');

        if (remove) {
            event.preventDefault();
            setValue(remove.closest('[data-iron-image]'), null);
        }
    });
}());
