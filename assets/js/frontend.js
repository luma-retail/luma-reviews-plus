document.addEventListener('DOMContentLoaded', function () {
    var i18n = window.lumaReviewsPlusI18n || {};
    var publicI18n = window.lumaReviewsPlusPublic || {};

    document.querySelectorAll('.luma-reviews-plus-message').forEach(function (message) {
        message.setAttribute('role', 'status');
    });

    var form = document.querySelector('.luma-reviews-plus-form');

    var setError = function (field, message) {
        var formRow = field.closest('.form-row, .comment-form-rating');
        var error = formRow ? formRow.querySelector('.luma-reviews-plus-field-error') : null;

        field.classList.add('luma-reviews-plus-invalid');

        if (error) {
            error.textContent = message;
            error.hidden = false;
        }
    };

    var clearError = function (field) {
        var formRow = field.closest('.form-row, .comment-form-rating');
        var error = formRow ? formRow.querySelector('.luma-reviews-plus-field-error') : null;

        field.classList.remove('luma-reviews-plus-invalid');

        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
    };

    var getInputsForSection = function (section) {
        return Array.prototype.slice.call(section.querySelectorAll('textarea, input[type="text"], input[type="checkbox"], select'));
    };

    var getSectionFields = function (section) {
        return section.querySelector('.luma-reviews-plus-section-fields');
    };

    var setSectionState = function (section, state) {
        var fields = getSectionFields(section);
        var untouchedSummary = section.querySelector('[data-summary-untouched]');
        var skippedSummary = section.querySelector('[data-summary-skipped]');
        var stateInput = section.querySelector('[data-section-state-input]');

        section.dataset.sectionState = state;

        if (stateInput) {
            stateInput.value = state;
        }

        section.classList.toggle('is-reviewing', state === 'reviewing');
        section.classList.toggle('is-skipped', state === 'skipped');
        section.classList.toggle('is-untouched', state === 'untouched');

        if (fields) {
            fields.hidden = state !== 'reviewing';
        }

        if (untouchedSummary) {
            untouchedSummary.hidden = state !== 'untouched';
        }

        if (skippedSummary) {
            skippedSummary.hidden = state !== 'skipped';
        }

        getInputsForSection(section).forEach(function (input) {
            var preserveHiddenSelect = input.classList.contains('luma-reviews-plus-rating-select');
            input.disabled = state !== 'reviewing' && !preserveHiddenSelect;

            if (state !== 'reviewing') {
                clearError(input);
            }
        });

        var ratingSelect = section.querySelector('.luma-reviews-plus-rating-select');

        if (ratingSelect) {
            ratingSelect.disabled = state !== 'reviewing';
        }
    };

    var activateSection = function (section) {
        if (section.dataset.sectionState !== 'reviewing') {
            setSectionState(section, 'reviewing');
        }
    };

    var getActiveProductSections = function () {
        return Array.prototype.slice.call(document.querySelectorAll('.luma-review-section[data-review-section="product"][data-section-state="reviewing"]'));
    };

    document.querySelectorAll('.luma-review-section').forEach(function (section, index) {
        var initialState = section.dataset.sectionState || 'untouched';

        if (section.dataset.reviewSection === 'product' && index === 0 && initialState === 'untouched') {
            initialState = 'reviewing';
        }

        setSectionState(section, initialState);

        section.querySelectorAll('[data-section-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.getAttribute('data-section-action') === 'review') {
                    activateSection(section);
                } else {
                    setSectionState(section, 'skipped');
                }
            });
        });

        section.querySelectorAll('textarea, input[type="text"], input[type="checkbox"]').forEach(function (input) {
            input.addEventListener('focus', function () {
                activateSection(section);
            });

            input.addEventListener('input', function () {
                activateSection(section);
                clearError(input);
            });

            input.addEventListener('change', function () {
                activateSection(section);
                clearError(input);
            });
        });
    });

    document.querySelectorAll('.luma-reviews-plus-rating-field').forEach(function (field) {
        var select = field.querySelector('.luma-reviews-plus-rating-select');
        var stars = field.querySelectorAll('.stars a');

        if (!select || !stars.length) {
            return;
        }

        var updateStars = function (value) {
            var hasValue = value !== '';
            var starsWrapper = field.querySelector('.stars');

            if (starsWrapper) {
                starsWrapper.classList.toggle('selected', hasValue);
            }

            stars.forEach(function (star, index) {
                var starValue = String(index + 1);
                var isActive = starValue === value;

                star.classList.toggle('active', isActive);
                star.setAttribute('aria-checked', isActive ? 'true' : 'false');
                star.setAttribute('tabindex', isActive || (!hasValue && index === 0) ? '0' : '-1');
            });
        };

        updateStars(select.value);

        stars.forEach(function (star) {
            star.addEventListener('click', function (event) {
                event.preventDefault();
                var section = field.closest('.luma-review-section');

                if (section) {
                    activateSection(section);
                }

                select.value = star.getAttribute('data-value') || '';
                updateStars(select.value);
                clearError(select);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    document.querySelectorAll('[data-luma-shop-quotes-load-more]').forEach(function (button) {
        var quotesContainer = button.closest('.luma-shop-reviews-summary__body').querySelector('[data-luma-shop-quotes]');

        if (!quotesContainer || !publicI18n.ajaxUrl) {
            return;
        }

        button.addEventListener('click', function () {
            if (button.disabled) {
                return;
            }

            var offset = parseInt(quotesContainer.getAttribute('data-offset') || '0', 10);
            var total = parseInt(quotesContainer.getAttribute('data-total') || '0', 10);
            var limit = parseInt(quotesContainer.getAttribute('data-load-count') || '3', 10);
            var minimumRating = parseInt(quotesContainer.getAttribute('data-minimum-rating') || '4', 10);
            var featuredOnly = quotesContainer.getAttribute('data-featured-only') === '1' ? '1' : '0';
            var nonce = button.getAttribute('data-nonce') || '';

            if (offset >= total) {
                button.hidden = true;
                return;
            }

            var formData = new window.FormData();
            formData.append('action', 'luma_reviews_plus_load_shop_quotes');
            formData.append('nonce', nonce);
            formData.append('offset', String(offset));
            formData.append('limit', String(limit > 0 ? limit : 3));
            formData.append('minimum_rating', String(minimumRating > 0 ? minimumRating : 4));
            formData.append('featured_only', featuredOnly);

            button.disabled = true;
            button.classList.add('is-loading');
            button.textContent = publicI18n.loadingLabel || button.textContent;

            window.fetch(publicI18n.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                var message;

                if (!payload || !payload.success || !payload.data) {
                    message = publicI18n.errorLabel || '';
                    if (message) {
                        window.alert(message);
                    }
                    return;
                }

                if (payload.data.html) {
                    quotesContainer.insertAdjacentHTML('beforeend', payload.data.html);
                }

                quotesContainer.setAttribute('data-offset', String(payload.data.next_offset || offset));

                if (!payload.data.has_more) {
                    button.hidden = true;
                }
            }).catch(function () {
                var message = publicI18n.errorLabel || '';
                if (message) {
                    window.alert(message);
                }
            }).finally(function () {
                button.disabled = false;
                button.classList.remove('is-loading');
                button.textContent = publicI18n.moreLabel || 'Show more';
            });
        });
    });

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        var summary = form.querySelector('.luma-reviews-plus-form-summary');
        var commentRequired = form.getAttribute('data-comment-required') === '1';
        var allowShopOnly = form.getAttribute('data-allow-shop-only') === '1';
        var firstInvalid = null;
        var hasActiveSection = false;
        var hasActiveProduct = false;
        var hasErrors = false;

        form.querySelectorAll('.luma-reviews-plus-invalid').forEach(function (field) {
            clearError(field);
        });

        document.querySelectorAll('.luma-review-section').forEach(function (section) {
            if (section.dataset.sectionState !== 'reviewing') {
                return;
            }

            hasActiveSection = true;

            var sectionType = section.getAttribute('data-review-section');
            var rating = section.querySelector('.luma-reviews-plus-rating-select');
            var comment = section.querySelector('textarea');

            if (sectionType === 'product') {
                hasActiveProduct = true;
            }

            if (rating && !rating.value) {
                hasErrors = true;
                setError(rating, sectionType === 'shop' ? i18n.shopRatingRequired : i18n.productRatingRequired);
                firstInvalid = firstInvalid || rating;
            }

            if (sectionType === 'product' && commentRequired && comment && !comment.value.trim()) {
                hasErrors = true;
                setError(comment, i18n.productCommentRequired);
                firstInvalid = firstInvalid || comment;
            }
        });

        var shopSection = form.querySelector('.luma-review-section[data-review-section="shop"]');

        if (shopSection && shopSection.dataset.sectionState === 'reviewing') {
            var displayName = shopSection.querySelector('input[name="shop_review[display_name]"]');

            if (displayName && !displayName.value.trim()) {
                hasErrors = true;
                setError(displayName, i18n.shopDisplayNameRequired);
                firstInvalid = firstInvalid || displayName;
            }

            if (!allowShopOnly && !hasActiveProduct) {
                hasErrors = true;
                firstInvalid = firstInvalid || shopSection.querySelector('.luma-reviews-plus-rating-select');
            }
        }

        if (!hasActiveSection) {
            hasErrors = true;
            if (summary) {
                summary.textContent = i18n.selectAtLeastOne;
                summary.hidden = false;
            }
        } else if (!allowShopOnly && shopSection && shopSection.dataset.sectionState === 'reviewing' && !hasActiveProduct) {
            if (summary) {
                summary.textContent = i18n.selectProductForStore;
                summary.hidden = false;
            }
        } else if (hasErrors) {
            if (summary) {
                summary.textContent = i18n.reviewErrorsSummary;
                summary.hidden = false;
            }
        } else if (summary) {
            summary.textContent = '';
            summary.hidden = true;
        }

        if (hasErrors) {
            event.preventDefault();

            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
});