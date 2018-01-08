(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

/**
 * Created by leo108 on 2016/9/23.
 */

Vue.component('admin-service-index', {
    data: function data() {
        return {
            query: {
                search: ''
            },
            services: [],
            editService: {
                id: 0,
                name: '',
                enabled: true,
                allow_proxy: false,
                hosts: ''
            },
            busy: false,
            isEdit: false
        };
    },
    ready: function ready() {
        this.services = Laravel.data.services.data;
        this.query = Laravel.data.query;
    },

    methods: {
        bool2icon: function bool2icon(value) {
            var cls = value ? 'fa-check' : 'fa-times';
            return '<i class="fa ' + cls + '"></i>';
        },
        displayHosts: function displayHosts(hostArr) {
            var glu = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '<br/>';

            var arr = [];
            for (var x in hostArr) {
                arr.push(hostArr[x].host);
            }
            return arr.join(glu);
        },
        edit: function edit(item) {
            this.isEdit = true;
            this.editService.id = item.id;
            this.editService.name = item.name;
            this.editService.enabled = item.enabled;
            this.editService.allow_proxy = item.allow_proxy;
            this.editService.hosts = this.displayHosts(item.hosts, "\n");
            $('#edit-dialog').modal();
        },
        showAdd: function showAdd() {
            this.isEdit = false;
            this.editService.id = 0;
            this.editService.name = '';
            this.editService.hosts = '';
            this.editService.enabled = true;
            this.editService.allow_proxy = false;
            $('#edit-dialog').modal();
        },
        save: function save() {
            if (this.isEdit) {
                this.update();
            } else {
                this.store();
            }
        },
        store: function store() {
            var _this = this;

            this.busy = true;
            this.$http.post(Laravel.router('service.store'), this.editService).then(function (response) {
                _this.busy = false;
                alert(response.data.msg);
                location.reload();
            });
        },
        update: function update() {
            var _this2 = this;

            this.busy = true;
            this.$http.put(Laravel.router('service.update', { service: this.editService.id }), this.editService).then(function (response) {
                _this2.busy = false;
                alert(response.data.msg);
                location.reload();
            });
        }
    }
});

},{}]},{},[1]);

//# sourceMappingURL=index.js.map
