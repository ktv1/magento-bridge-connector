require([
      'jquery'
  ],function ($) {
        var setupButton = $('.btn-setup');
        var contentBlockManage = $('#content-block');
        var bridgeStoreKey = $('#bridgeStoreKey');
        var storeKey = $('#storeKey');
        var storeBlock = $('.store-key');
        var classMessage = $('.message');
        var progress = $('.progress');

        var updateBridgeStoreKey = $('#updateBridgeStoreKey');

        if (storeKey.val() == '') {
            contentBlockManage.html(bridgeNotInstalledMsg);
            storeBlock.fadeOut(0);
            updateBridgeStoreKey.hide();
        } else {
            contentBlockManage.html(bridgeInstalledMsg);
            storeBlock.fadeIn();
            updateBridgeStoreKey.show();
        }

        function statusMessage(message, status)
        {
            if (status == 'success') {
                classMessage.removeClass('bridge_error');
            } else {
                classMessage.addClass('bridge_error');
            }
            classMessage.html('<span>' + message + '</span>');
            classMessage.fadeIn("slow");
            var messageClear = setTimeout(function () {
                classMessage.fadeOut(1000);
                clearTimeout(messageClear);
            }, 3000);
        }

        $('.btn-setup').click(function () {
            var self = $(this);
            $(this).attr("disabled", true);
            progress.slideDown("fast");
            var actionName = 'Install';
            if (storeKey.val() != '') {
                actionName = 'Uninstall';
            }

            $.ajax({
                cache: false,
                method: 'POST',
                type:'POST',
                url: SELF_PATH + actionName.toLowerCase(),
                data: {'form_key' : window.FORM_KEY, 'plugin_key' : window.pluginKey},
                dataType: 'json',
                error: function (jqXHR, textStatus) {
                    statusMessage('Can not install Connector: ' + textStatus  ,'error');
                    progress.slideUp("fast");
                    $('.btn-setup').attr("disabled", false);
                },
                success: function (data) {
                    self.attr("disabled", false);
                    progress.slideUp("fast");

                    if (data.error != null || data.result == false) {
                        statusMessage('Can not ' + actionName + ' Connector: ' + data.error, 'error');
                        return;
                    }

                    if (actionName == 'Install') {
                        contentBlockManage.html(bridgeInstalledMsg);
                        updateStoreKey(data.result);
                        setupButton.html(buttonUninstallMsg);
                        setupButton.removeClass('btn-connect');
                        setupButton.addClass('btn-disconnect');

                        storeBlock.fadeIn("slow");
                        updateBridgeStoreKey.fadeIn("slow");
                        statusMessage('Connector Installed Successfully','success');
                    } else {
                        setupButton.html(buttonInstallMsg);
                        setupButton.removeClass('btn-disconnect');
                        setupButton.addClass('btn-connect');

                        contentBlockManage.html(bridgeNotInstalledMsg);
                        storeBlock.fadeOut("fast");
                        updateBridgeStoreKey.fadeOut("fast");
                        updateStoreKey('');
                        statusMessage('Connector Uninstalled Successfully','success');
                    }
                }
            });
        });

        updateBridgeStoreKey.click(function () {
            progress.slideDown("fast");
            $.ajax({
                cache: false,
                method: 'POST',
                type:'POST',
                url: SELF_PATH + 'update/key',
                data: {'form_key' : window.FORM_KEY, 'plugin_key' : window.pluginKey},
                dataType: 'json',
                success: function (data) {
                    if (data.error != null || data.result == false) {
                        statusMessage('Can not update store key:' + data.error,'error');
                        return;
                    }
                    updateStoreKey(data.result);
                    statusMessage('Store key updated successfully!','success');
                    progress.slideUp("fast");
                },
                error: function (jqXHR, textStatus) {
                    progress.slideUp("fast");
                    statusMessage('Can not update store key: ' + textStatus  ,'error');
                }
            });
        });

        function updateStoreKey(store_key)
        {
            storeKey.val(store_key);
        }
    }
);

function Message(element)
{
    var e = element;
    this.show = function (textMsg, type) {
        e.finish();
        e.text(textMsg).show().fadeOut(12000);
        if (type == 'error') {
            e.addClass('bridge_error');
        } else {
            e.removeClass('bridge_error');
        }
    };
}