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

    /* ---------------------------------------------------------------------- */
    /* Listes répétables                                                      */
    /* ---------------------------------------------------------------------- */

    /**
     * Les index de ligne ne sont jamais réécrits côté navigateur : PHP
     * réindexe à la sauvegarde d'après l'ordre de soumission, qui est celui du
     * DOM. Ajouter, supprimer et déplacer se réduisent donc à des
     * manipulations de nœuds.
     */

    function rowsOf(repeater) {
        return repeater.querySelectorAll('[data-iron-repeater-row]');
    }

    function refresh(repeater) {
        var count = rowsOf(repeater).length;
        var max = parseInt(repeater.getAttribute('data-iron-max'), 10) || 0;
        var min = parseInt(repeater.getAttribute('data-iron-min'), 10) || 0;

        var addButton = repeater.querySelector('[data-iron-repeater-add]');
        var empty = repeater.querySelector('[data-iron-repeater-empty]');

        if (addButton) {
            addButton.disabled = max > 0 && count >= max;
        }

        if (empty) {
            empty.hidden = count > 0;
        }

        rowsOf(repeater).forEach(function (row, index) {
            var up = row.querySelector('[data-iron-repeater-up]');
            var down = row.querySelector('[data-iron-repeater-down]');
            var del = row.querySelector('[data-iron-repeater-remove]');

            if (up) {
                up.disabled = 0 === index;
            }

            if (down) {
                down.disabled = index === count - 1;
            }

            if (del) {
                del.disabled = count <= min;
            }
        });
    }

    function addRow(repeater) {
        var template = repeater.querySelector('[data-iron-repeater-template]');
        var container = repeater.querySelector('[data-iron-repeater-rows]');

        if (!template || !container) {
            return;
        }

        var next = parseInt(repeater.getAttribute('data-iron-next'), 10) || 0;

        // Un compteur monotone, jamais réutilisé : deux lignes ne peuvent pas
        // se retrouver avec le même index dans le même formulaire.
        repeater.setAttribute('data-iron-next', String(next + 1));

        container.insertAdjacentHTML(
            'beforeend',
            template.innerHTML.split('__INDEX__').join(String(next))
        );

        refresh(repeater);
    }

    /**
     * Une ligne qu'on vient d'ajouter et qui est encore vide se supprime sans
     * question : demander confirmation pour rien apprend au client à cliquer
     * « oui » sans lire, et la confirmation ne protège alors plus rien.
     */
    function rowIsEmpty(row) {
        var champs = row.querySelectorAll('input[type="text"], input[type="url"], input[type="hidden"], textarea, select');

        for (var i = 0; i < champs.length; i++) {
            if ('' !== champs[i].value && '0' !== champs[i].value) {
                return false;
            }
        }

        return true;
    }

    function moveRow(row, direction) {
        var sibling = 'up' === direction
            ? row.previousElementSibling
            : row.nextElementSibling;

        if (!sibling) {
            return;
        }

        if ('up' === direction) {
            row.parentNode.insertBefore(row, sibling);
        } else {
            row.parentNode.insertBefore(sibling, row);
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest(
            '[data-iron-repeater-add],[data-iron-repeater-remove],[data-iron-repeater-up],[data-iron-repeater-down]'
        );

        if (!button) {
            return;
        }

        var repeater = button.closest('[data-iron-repeater]');

        if (!repeater) {
            return;
        }

        event.preventDefault();

        if (button.hasAttribute('data-iron-repeater-add')) {
            addRow(repeater);
            return;
        }

        var row = button.closest('[data-iron-repeater-row]');

        if (!row) {
            return;
        }

        if (button.hasAttribute('data-iron-repeater-remove')) {
            if (!rowIsEmpty(row) && !window.confirm(l10n.confirmRemoveRow || 'Supprimer cette ligne ?')) {
                return;
            }

            row.remove();
        } else {
            moveRow(row, button.hasAttribute('data-iron-repeater-up') ? 'up' : 'down');
        }

        refresh(repeater);
    });

    /* ---------------------------------------------------------------------- */
    /* Interrupteur de section                                                */
    /* ---------------------------------------------------------------------- */

    /**
     * Les champs d'une section masquée restent modifiables : le client peut
     * préparer son contenu avant de l'afficher. On atténue simplement leur
     * rendu pour qu'il voie d'un coup d'oeil ce qui ne sortira pas sur le site.
     */
    function refreshGroupToggle(checkbox) {
        var box = checkbox.closest('.postbox') || checkbox.closest('.iron-options__group');

        if (!box) {
            return;
        }

        box.querySelectorAll('.iron-fields').forEach(function (fields) {
            fields.classList.toggle('iron-fields--off', !checkbox.checked);
        });
    }

    document.addEventListener('change', function (event) {
        var checkbox = event.target.closest('[data-iron-group-toggle]');

        if (checkbox) {
            refreshGroupToggle(checkbox);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-iron-repeater]').forEach(refresh);
        document.querySelectorAll('[data-iron-group-toggle]').forEach(refreshGroupToggle);
    });
}());
