(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

/**
 * Created by leo108 on 16/9/20.
 */
$(document).ready(function () {
    var $pwdDialog = $('#change-pwd-dialog');
    $('#btn_logout').click(function () {
        bootbox.confirm(Laravel.trans('message.confirm_logout'), function (ret) {
            if (ret) {
                location.href = Laravel.router('logout');
            }
        });
    });

    $('#btn_change_pwd').click(function () {
        $pwdDialog.modal();
    });

    $('#btn-save-pwd').click(function () {
        $pwdDialog.find('div.form-group').removeClass('has-error');
        var map = {
            'old': 'old-pwd',
            'new1': 'new-pwd',
            'new2': 'new-pwd2'
        };
        var val = {};

        for (var x in map) {
            var $input = $('#' + map[x]);
            val[x] = $input.val();
            if ($input.val() == '') {
                $input.closest('div.form-group').addClass('has-error');
            }
        }

        if (val['new1'] != val['new2']) {
            $('#new-pwd2').closest('div.form-group').addClass('has-error');
        }

        if ($pwdDialog.find('.has-error').length > 0) {
            return;
        }

        var req = {
            'old': val['old'],
            'new': val['new1']
        };

        $.post(Laravel.router('change_pwd'), req, function (ret) {
            alert(ret.msg);
            $pwdDialog.modal('hide');
        }, 'json');
    });
});

},{}]},{},[1]);

//# sourceMappingURL=home.js.map
