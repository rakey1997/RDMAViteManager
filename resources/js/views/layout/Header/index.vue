<template>
    <div class="navbar">
        <hamburger />
        <breadcrumb />
        <div class="navbar-right">
            <driver class="navbar-item" />
            <screenful class="navbar-item" />
            <lang class="navbar-item"/>
            <avatar class="navbar-item"/>
        </div>        
    </div>
</template>

<script>
    import hamburger from './components/hamburger.vue'
    import breadcrumb from "./components/breadcrumb.vue";
    import avatar from "./components/avatar.vue";
    import lang from "./components/lang.vue";
    import screenful from "./components/screenful.vue";
    import driver from "./components/driver/index.vue";

    export default {
        name:'DNSHeader',
        components:{hamburger,breadcrumb,avatar,lang,screenful,driver}
    }
</script>

<style lang="scss" scoped>
    .navbar{
        width: 100%;
        height: 60px;
        overflow: hidden;
        background-color: #fff;
        box-shadow: 0 1px 4px rgba(0,21,41,0.08);
        padding: 0 16px;
        display: flex;
        align-items: center;
        box-sizing: border-box;
        position: relative;
        .navbar-right{
            flex:1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            :deep(.navbar-item){
                display: inline-block;
                margin-right: 18px;
                font-size: 22px;
                color: #5a5e66;
                box-sizing: border-box;
                cursor: pointer;
            }
        }
    }
</style>