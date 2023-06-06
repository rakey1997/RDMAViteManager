<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User\UserModel;

class UserController extends Controller
{
    private function delByValue($arr, $value){
        if(!is_array($arr)){
            return $arr;
        }
        foreach($arr as $k=>$v){
            if($v['username'] == $value){
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    protected function login(Request $request){
        $jsonArr=array();
        $userName=$request->input('username');
        $password=$request->input('password');

        $user_Info=new UserModel();
        $record=$user_Info->select('id','username','password','role','state','api_token')->where('username',$userName)->get()->toArray(); 
        // print_r($record);
        if (!empty($record)){
            if ($password==$record[0]['password']){
                if($record[0]['state']==1){
                    $jsonArr['opCode']=true;
                    $jsonArr['result']='登录成功';
                    $jsonArr['userid']=$record[0]['id'];  
                    $jsonArr['role']=$record[0]['role'];  
                    $jsonArr['api_token']=$record[0]['api_token'];  
                }else{
                    $jsonArr['opCode']=false;
                    $jsonArr['result']='登录失败，用户已被禁用';
                }
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['result']='登录失败，密码输入错误';
            }          
        }else {
            $jsonArr['opCode']=false;
            $jsonArr['result']='登录失败，用户名不存在';
        }
        return $jsonArr;
    }

    protected function returnUser(Request $request){
        $jsonArr=array();
        $userName=$request->input('query');
        $pagenum=$request->input('pagenum');
        $pagesize=$request->input('pagesize');

        $skipNum=($pagenum-1)*$pagesize;

        $user_Info=new UserModel();
        
        if($userName==''){
            $total=$user_Info->count();
            $record=$user_Info->select('id','username','email','role','state','create_time')->orderBy('create_time','asc')->skip($skipNum)->take($pagesize)->get()->toArray(); 
        }else{
            $total=$user_Info->where('username','like', '%'.$userName.'%')->count();
            $record=$user_Info->select('id','username','email','role','state','create_time')->orderBy('create_time','asc')->where('username','like', '%'.$userName.'%')->skip($skipNum)->take($pagesize)->get()->toArray(); 
        }

        $jsonArr['pagenum']=$pagenum;
        $jsonArr['pagesize']=$pagesize;
        $jsonArr['total']=$total;
        if (!empty($record)){
            $jsonArr['users']=$record;
            $jsonArr['total']=$total;
        }else {
            $jsonArr['users']=[];
            $jsonArr['total']=0;
        }
        $jsonArr['opCode']=true;
        return $jsonArr;
    }

    protected function updateUser($userID,$state){
        $jsonArr=array();
        $user_Info=new UserModel();
        $user_match=$user_Info->where('id',$userID)->get();

        if (!empty($user_match)){
            $res=$user_Info->where('id',$userID)->update(['state'=>$state]);
            if ($res==1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='update success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='update fail';
            }
            return $jsonArr;
        }else{
            return response()->json(
                ['message' => 'Record not found.']
            , 404);
        }
    }

    protected function addUser(Request $request){
        $jsonArr=array();
        $user_Info=new UserModel();
        $data=$request->input();
        $user_match=$user_Info->where('username',$data['username'])->get();

        $token = hash('sha256', random_bytes(32)); //随机生成64位字符串

        if (count($user_match)===0){
            $res=$user_Info->create([
                "username"=>$data['username'], 
                "email"=>$data['email'],
                "password"=>$data['password'],
                "role"=>$data['role'],
                "state"=>$data['state'],
                'api_token' => $token
            ])->save();
            if ($res==1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='add record success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='add record fail';
            }
            return $jsonArr;
        }else{
            return response()->json(
                ['message' => 'User already exsist!']
            , 404);
        }
    }

    protected function editUser($userID,Request $request){
        $jsonArr=array();
        $user_Info=new UserModel();
        $data=$request->input();
        $user_match=$user_Info->where('id',$userID)->get();
        $token = hash('sha256', random_bytes(32)); //随机生成64位字符串

        if (!empty($user_match)){
            $res=$user_Info->where('id',$userID)->update([
                "username"=>$data['username'], 
                "email"=>$data['email'],
                "role"=>$data['role'],
                'state'=>$data['state'],
                'api_token' => $token
            ]);
            if ($res==1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='update success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='update fail';
            }
            return $jsonArr;
        }else{
            return response()->json(
                ['message' => 'Record not found.']
            , 404);
        }
    }

    protected function deleteUser($ids){
        $jsonArr=array();
        $user_Info=new UserModel();
        $flag=false;
        $userIDs=explode(',', $ids);
        foreach ($userIDs as $userID){
            $user_match=$user_Info->where('id',$userID)->get();
            if (empty($user_match)){
                return response()->json(
                    ['message' => $userID.' Record not found.']
                , 404);
                $flag=true;
            }
        }
        if ($flag){
            return response()->json(
                ['message' => 'please check record..']
            , 404);
        }else{
            $res=$user_Info->destroy($userIDs);
            if ($res>=1){
                $jsonArr['opCode']=true;
                $jsonArr['msg']='delete success';
            }else{
                $jsonArr['opCode']=false;
                $jsonArr['msg']='delete fail';
            }
            return $jsonArr;
        }
    }
}
