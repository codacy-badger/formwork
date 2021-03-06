(function ($) {
    $.fn.tagInput = function () {

        function _update($input) {
            var $parent = $input.parent();
            $('.tag-hidden-input', $parent).val($parent.data('tags').join(', '));
        }

        function _createTag($input, value) {
            $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
        }

        function addTag($input, value) {
            if ($input.parent().data('tags').indexOf(value) === -1) {
                $input.parent().data('tags').push(value);
                _createTag($input, value);
                _update($input);
            }
            $input.val('');
        }

        function removeTag($input, value) {
            var tags = $input.parent().data('tags');
            var index = tags.indexOf(value);
            if (index > -1) {
                tags.splice(index, 1);
                $input.parent().data('tags', tags);
                _update($input);
            }
        }

        this.each(function () {
            var $this = $(this);
            var $target = $('.tag-hidden-input', $this);
            var $input = $('.tag-inner-input', $this);
            var tags = [];
            if ($target.val()) {
                tags = $target.val().split(', ');
                $.each(tags, function (index, value) {
                    value = value.trim();
                    tags[index] = value;
                    _createTag($input, value);
                });
            }
            $this.data('tags', tags)
                .on('mousedown', '.tag-remove', false)
                .on('click', '.tag-remove', function () {
                    var $tag = $(this).parent();
                    removeTag($input, $tag.text());
                    $tag.remove();
                    return false;
                });
        });

        this.on('mousedown', function () {
            $('.tag-inner-input', this).trigger('focus');
            return false;
        });

        $('.tag-inner-input', this).on('focus', function () {
            $(this).parent().addClass('focused');
        }).on('blur', function () {
            var $this = $(this);
            var value = $this.val().trim();
            if (value !== '') {
                addTag($this, value);
                $this.prop('size', 1);
            }
            $this.parent().removeClass('focused');
        }).on('keydown', function (event) {
            var options = {addKeyCodes: [32]};
            var $this = $(this);
            var value = $this.val().trim();

            switch (event.which) {
            case 8: // backspace
                if (value === '') {
                    removeTag($this, $this.prev().text());
                    $this.prev().remove();
                    $this.prop('size', 1);
                    return false;
                }
                $this.prop('size', Math.max($this.val().length, 1));
                return true;
            case 13: // enter
            case 188: // comma
                if (value !== '') {
                    addTag($this, value);
                }
                $this.prop('size', 1);
                return false;
            default:
                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                    addTag($this, value);
                    $this.prop('size', 1);
                    return false;
                }
                $this.prop('size', $this.val().length + 2);
                break;
            }

        });
    };
}(jQuery));
