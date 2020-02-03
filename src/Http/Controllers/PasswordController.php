<?php

namespace Appoly\LaravelApiPasswordHelper\Http\Controllers;

use Appoly\LaravelApiPasswordHelper\Http\Notifications\ResetPassword;
use Illuminate\Http\Request;

class PasswordController
{
    public function forgot(Request $request)
    {
        if ($request->has('email')) {
            $password_helper_key = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $user = \App\User::where('email', $request->email)->first();

            if ($user) {
                $user->password_helper_key = $password_helper_key;
                $user->save();

                $user->notify(new ResetPassword($user));

                return response()->json(
                        ['message' => 'An email has been sent to your account'],
                     200);
            } else {
                return response()->json(
                    ['message' => 'No user found'],
                 400);
            }
        }

        return response()->json(['message' => 'Email is required'], 400);
    }

    public function reset(Request $request)
    {
        if ($request->has('key') && $request->has('password')) {
            $user = \App\User::where('password_helper_key', $request->key)->first();

            if ($user) {
                $user->update([
                    'password' => bcrypt($request->password),
                    'password_helper_key' => null,
                ]);

                return response()->json(
                    ['message' => 'Your password has been updated']
                , 200);
            } else {
                return response()->json([
                    ['message' => 'No user found'],
                ], 400);
            }
        }

        return response()->json([
            ['message' => 'Key and Password are required'],
        ], 400);
    }
}
