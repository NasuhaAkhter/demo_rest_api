<?php

    namespace App;

    use Illuminate\Notifications\Notifiable;
    use Illuminate\Foundation\Auth\User as Authenticatable; 
    use Tymon\JWTAuth\Contracts\JWTSubject;
    use Illuminate\Contracts\Auth\MustVerifyEmail;
 
    class User extends Authenticatable implements JWTSubject, MustVerifyEmail
    {
        use Notifiable;
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = [
            'first_name','last_name','optional_email','organization','city','state','email','mobile','home_phone','office_phone',	'password',	'profile_picture',	'user_type','email_verified_at','email_verification_token',	'password_token', 'device_id'
        ];

        /**
         * The attributes that should be hidden for arrays.
         *
         * @var array
         */
        protected $hidden = [
            'password', 'remember_token',
        ];

        public function getJWTIdentifier()
        {
            return $this->getKey();
        }
        public function getJWTCustomClaims()
        {
            return [];
        }
    }