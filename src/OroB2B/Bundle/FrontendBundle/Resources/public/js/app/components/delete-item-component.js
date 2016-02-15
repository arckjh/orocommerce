/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DeleteItemComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    DeleteItemComponent = BaseComponent.extend({
        initialize: function(options) {
            this.$elem = options._sourceElement;
            this.url = options.url;
            this.removeClass = options.removeClass;
            this.confirmMessage = options.confirmMessage;
            this.sucsessMessage = options.sucsessMessage;

            this.$elem.on('click', _.bind(this.deleteItem, this));
        },
        deleteItem: function() {
            if (this.confirmMessage) {
                this.deleteWithConfirmation();
            } else {
                this.deleteWithoutConfirmation();
            }
        },
        deleteWithConfirmation: function() {
            var confirm = new DeleteConfirmation({
                content: this.confirmMessage
            });
            confirm.on('ok',_.bind(this.deleteWithConfirmation, this));
            confirm.open();

        },
        deleteWithoutConfirmation: function(e) {
            var self = this;
            $.ajax({
                url: self.url,
                type: 'DELETE',
                success: function() {
                    var message = self.sucsessMessage
                        || __('item_deleted');
                    self.$elem.closest('.' + self.removeClass).remove();
                    mediator.trigger('frontend:item:delete', e);
                    mediator.execute('showMessage', 'success', message);
                },
                error: function() {
                    var message = __('unexpected_error');
                    mediator.execute('hideLoading');
                    mediator.execute('showMessage', 'error', message);
                }
            })
        }
    });

    return DeleteItemComponent;
});