/**
 * Created by leo108 on 2016/9/23.
 */

Vue.component('admin-service-index', {
    data() {
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
                hosts: '',
                api:[],
            },
            busy: false,
            isEdit: false,
        }
    },
    ready() {
        this.services = Laravel.data.services.data;
        this.query = Laravel.data.query;
    },
    methods: {
        bool2icon(value) {
            let cls = value ? 'fa-check' : 'fa-times';
            return '<i class="fa ' + cls + '"></i>';
        },
        displayHosts(hostArr, glu = '<br/>') {
            let arr = [];
            for (let x in hostArr) {
                arr.push(hostArr[x].host);
            }
            return arr.join(glu);
        },
        addApi(){
            let e = {name:'', url:'', fields:'', response_fields:'', method:'GET'};
            this.editService.api.push(e);
        },
        removeApi(index){
            this.editService.api.splice(index,1);
        },
        edit(item) {
            this.isEdit = true;
            this.editService.id = item.id;
            this.editService.name = item.name;
            this.editService.enabled = item.enabled;
            this.editService.allow_proxy = item.allow_proxy;
            this.editService.hosts = this.displayHosts(item.hosts, "\n");
            this.editService.api = item.apis;
            $('#edit-dialog').modal();
        },
        showAdd() {
            this.isEdit = false;
            this.editService.id = 0;
            this.editService.name = '';
            this.editService.hosts = '';
            this.editService.enabled = true;
            this.editService.allow_proxy = false;
            this.editService.api = [];
            $('#edit-dialog').modal();
        },
        save() {
            if (this.isEdit) {
                this.update();
            } else {
                this.store();
            }
        },
        store(){
            this.busy = true;
            this.$http.post(Laravel.router('service.store'), this.editService)
                .then(response => {
                    this.busy = false;
                    alert(response.data.msg);
                    location.reload();
                })
        },
        update(){
            this.busy = true;
            this.$http.put(Laravel.router('service.update', {service: this.editService.id}), this.editService)
                .then(response => {
                    this.busy = false;
                    alert(response.data.msg);
                    location.reload();
                })
        },
    }
});
