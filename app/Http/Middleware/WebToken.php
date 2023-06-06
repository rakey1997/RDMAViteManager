<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use App\Models\User\UserModel;

class WebToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userid=$request->header('userid');
        $web_token=$request->header('authorization');

        $user_Info=new UserModel();
        $record=$user_Info->select('api_token')->where('id',$userid)->get()->toArray();
        
        if (empty($web_token)) {
            return response('Token Invalid',401);
        }elseif($record[0]['api_token']!=$web_token){
            // return redirect('vue#');
            return response('Token Invalid',401);
        }

        return $next($request);
    }
}
