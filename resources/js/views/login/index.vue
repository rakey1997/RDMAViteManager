<template>
  <div class="login-container">
    <el-form ref="formRef" :model="loginForm" :rules="loginRules" class="login-form" >
      <div class="title-container">
        <h2 class="title">{{$t('login.title')}}</h2>
      </div>

      <el-form-item prop="username">
        <el-icon :size="20" class="svg-container">
          <User />
        </el-icon>
        <el-input v-model="loginForm.username" :placeholder="$t('dialog.username')" clearable/>
      </el-form-item>

      <el-form-item prop="password">
        <el-icon :size="20" class="svg-container">
          <Edit />
        </el-icon>
        <el-input 
          v-model="loginForm.password" 
          type="password"
          :placeholder="$t('dialog.password')"
          show-password />
      </el-form-item>

      <el-button type="primary" style="width:100%;margin-bottom:30px;" @click.prevent="handleLogin">{{$t('login.btnTitle')}}</el-button>
    </el-form>
  </div> 
</template>

<script>
  import { ref,reactive } from "vue";
  import { useStore } from "vuex";
  import { useI18n } from "vue-i18n";

  export default {
    name: 'login',
    setup() {
      const store=useStore()
      const {t}=useI18n()

      const loginForm = reactive({
          username: 'super',
          password:'Changeme_123',
          })
      
      const loginRules = reactive({
          username: [
              { required: true, message: t('login.warnUserNull'), trigger: 'blur' },
              { min: 3, max: 12, message: t('login.warnUserLen'), trigger: 'blur' },
            ],
          password: [
              { required: true, message: t('login.warnPasswordNull'), trigger: 'blur' },
              { min: 8, max: 16, message: t('login.warnPasswordLen'), trigger: 'blur' },
            ],
          })
      
      const formRef=ref(null)
      const handleLogin=()=>{
        formRef.value.validate(async (valid) => {
          if (valid) {
            store.dispatch('app/login',loginForm)
            store.dispatch('app/testHostPair',JSON.stringify([]))
          }
        })
      }

      return {
        loginForm,
        loginRules,
        formRef,
        handleLogin,
        store
      }
    },
  };
</script>


<style lang="scss" scoped>
$bg:#2d3a4b;
$dark_gray:#889aa4;
$light_gray:#eee;
$cursor:#fff;

.login-container{
  height: 100%;
  width: 100%;
  background-color: $bg;
  overflow: hidden;
  .login-form{
    position: relative;
    width: 520px;
    max-width: 100%;
    padding: 160px 35px 0;
    margin: 0 auto;
    overflow: hidden;

  :deep(:deep(.el-form-item)) {
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 5px;
    color: #454545;
  }
  :deep(:deep(.el-input)){
    display: inline-block;
    height: 47px;
    width: 60%;
  
    input{
      box-shadow: 0 0 0 0;
      background: transparent;
      border: 0px;
      // -webkit-appearance: none;
      border-radius: 0px;
      padding: 12px 5px 12px 15px;
      color: $light_gray;
      height: 47px;
      caret-color: $cursor;
    }
  }
  .login-button{
    width: 100%;
    box-sizing: border-box;
  }
}

  .tips{
    font-size: 16px;
    line-height: 28px;
    color: #fff;
    margin-bottom: 10px;

    span{
      &:first-of-type{
        margin-right: 16px;
      }
    }
  }
  .svg-container{
    padding: 6px 5px 26px 15px;
    color: $dark_gray;
    vertical-align: middle;
    display: inline-block;
  }
  .title-container{
    position:relative;
    .title{
      font-size:40px;
      color: $light_gray;
      margin:0px auto 40px auto;
      text-align: center;
      font-weight: bold;
    }

    :deep(:deep(.lang-select)){
      position: absolute;
      top:4px;
      right:0;
      background-color: white;
      font-size: 22px;
      padding: 4px;
      border-radius: 4px;
      cursor: pointer;
    }
  }
  .show-pwd{
    font-size: 16px;
    color: $dark_gray;
    cursor: pointer;
    user-select: none;
  }
}
</style>