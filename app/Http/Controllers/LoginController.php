<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        if(auth()->check()){
            return response()->json(['status' => true, 'message' => 'Usuário já esta logado.']);
        }
        $urlIntended = session()->pull('url.roomUrl') ?? '/';
        if($request->post('isGuest')){
            if(User::guestLogin($request->all())){
                return response()->json(['status' => true, 'message' => 'Bem vindo', 'intended' => $urlIntended]);
            }
            return response()->json(['status' => false, 'message' => 'Não foi possivel entrar como visitante.']);
        }

 
        $request->validate(User::getRules()['rules'],User::getRules()['messages']);
        if(!User::login($request->all())){
            return response()->json(['status' => false, 'errors' => [
                'email' => [
                    'Usuário e/ou senha inválidos'
                ]
            ]],422);

        }
        return response()->json(['status' => true, 'message' => 'Bem vindo ' . auth()->user()->name,'user' => [
            'email' => auth()->user()->email,
            'name' => auth()->user()->name
        ],'intended' => $urlIntended]);


    }

    public function logout()
    {
       \Auth::logout();
        return response()->json(['status' => true, 'message' => 'Usuário deslogado']);
    }

    public function guestLogin()
    {
    }
}
