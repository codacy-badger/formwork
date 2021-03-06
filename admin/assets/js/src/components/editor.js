Formwork.Editor = function (id) {
    var textarea = $('#' + id)[0];
    var $toolbar = '.editor-toolbar[data-for=' + id + ']';
    restoreCursorPosition();

    $('[data-command=bold]', $toolbar).on('click', function () {
        insertAtCursor('**');
    });

    $('[data-command=italic]', $toolbar).on('click', function () {
        insertAtCursor('_');
    });

    $('[data-command=ul]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '- ', '');
    });

    $('[data-command=ol]', $toolbar).on('click', function () {
        var num = /^\d+\./.exec(lastLine(textarea.value));
        if (num) {
            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(prependSequence() + '1. ', '');
        }
    });

    $('[data-command=quote]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '> ', '');
    });

    $('[data-command=link]', $toolbar).on('click', function () {
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var selection = startPos === endPos ? '' : textarea.value.substring(startPos, endPos);
        var left = textarea.value.substring(0, startPos);
        var right = textarea.value.substring(endPos, textarea.value.length);
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            textarea.value = left + '[](' + selection + ')' + right;
            textarea.trigger('focus');
            textarea.setSelectionRange(startPos + 1, startPos + 1);
        } else if (selection !== '') {
            textarea.value = left + '[' + selection + '](http://)' + right;
            textarea.trigger('focus');
            textarea.setSelectionRange(startPos + selection.length + 10, startPos + selection.length + 10);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', $toolbar).on('click', function () {
        Formwork.Modals.show('imagesModal', null, function ($modal) {
            $('.image-picker-thumbnail.selected', $modal).removeClass('selected');
            $('.image-picker-confirm', $modal).data('target', function (filename) {
                if (filename !== undefined) {
                    insertAtCursor(prependSequence() + '![', '](' + filename + ')');
                } else {
                    insertAtCursor(prependSequence() + '![](', ')');
                }
            });
        });
    });

    $('[data-command=summary]', $toolbar).on('click', function () {
        if (!hasSummarySequence()) {
            var prevChar = prevCursorChar();
            var prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(prepend + '\n===\n\n', '');
            $(this).attr('disabled', true);
        }
    });

    $(textarea).on('keyup', Formwork.Utils.debounce(disableSummaryCommand, 1000));
    disableSummaryCommand();

    $(document).on('keydown', function (event) {
        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
            switch (event.which) {
            case 66: // ctrl/cmd + B
                $('[data-command=bold]', $toolbar).trigger('click');
                return false;
            case 73: // ctrl/cmd + I
                $('[data-command=italic]', $toolbar).trigger('click');
                return false;
            case 75: // ctrl/cmd + K
                $('[data-command=link]', $toolbar).trigger('click');
                return false;
            case 89: // ctrl/cmd + Y
            case 90: // ctrl/cmd + Z
                return false;
            }
        }
    });

    $(window).on('beforeunload', retainCursorPosition);
    $(textarea).closest('form').on('submit', retainCursorPosition);

    function retainCursorPosition() {
        var data = [location.pathname, textarea.scrollTop, textarea.selectionEnd].join('#');
        if ($(textarea).is(':focus')) {
            window.sessionStorage.setItem('formworkEditorCursorPosition', data);
        } else {
            window.sessionStorage.removeItem('formworkEditorCursorPosition');
        }
    }

    function restoreCursorPosition() {
        var data = window.sessionStorage.getItem('formworkEditorCursorPosition');
        if (data !== null) {
            data = data.split('#');
            if (data[0] === location.pathname) {
                textarea.scrollTop = data[1];
                textarea.setSelectionRange(data[2], data[2]);
                $(textarea).trigger('focus');
            }
        }
    }

    function hasSummarySequence() {
        return /\n+===\n+/.test(textarea.value);
    }

    function disableSummaryCommand() {
        $('[data-command=summary]', $toolbar).attr('disabled', hasSummarySequence());
    }

    function lastLine(text) {
        var index = text.lastIndexOf('\n');
        if (index === -1) {
            return text;
        }
        return text.substring(index + 1);
    }

    function prevCursorChar() {
        var startPos = textarea.selectionStart;
        return startPos === 0 ? undefined : textarea.value.substring(startPos - 1, startPos);
    }

    function prependSequence() {
        switch (prevCursorChar()) {
        case undefined:
            return '';
        case '\n':
            return '\n';
        default:
            return '\n\n';
        }
    }

    function insertAtCursor(leftValue, rightValue) {
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var selection = startPos === endPos ? '' : textarea.value.substring(startPos, endPos);
        textarea.value = textarea.value.substring(0, startPos) + leftValue + selection + rightValue + textarea.value.substring(endPos, textarea.value.length);
        textarea.setSelectionRange(startPos + leftValue.length, startPos + leftValue.length + selection.length);
        $(textarea).trigger('blur').trigger('focus');
    }
};
