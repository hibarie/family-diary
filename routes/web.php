<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

// このAPIはトークン認証(Sanctum)のみを想定しており、Web用のログイン画面は無い。
// Accept: application/jsonを付けずにAPIへアクセスした場合など、Laravelの認証ミドルウェアが
// 「ログイン画面へリダイレクトしよう」としてroute('login')を探してしまうことがあるため、
// エラーにならないよう、JSONで401を返すだけの受け皿として名前付きルートを用意しておく。
Route::get('/login', function () {
    return response()->json([
        'message' => 'このAPIはトークン認証が必要です。/api/loginでログインしてトークンを取得してください。',
    ], 401);
})->name('login');
