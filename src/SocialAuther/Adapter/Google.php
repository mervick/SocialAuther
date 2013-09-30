<?php

namespace SocialAuther\Adapter;

class Google extends AbstractAdapter
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->fieldsMap = array(
            'id'         => 'id',
            'email'      => 'email',
            'page'       => 'link',
            'image'      => 'picture',
            'firstName'  => 'given_name',
            'secondName' => 'family_name'
        );

        $this->provider = 'google';
    }

    /**
     * Get user sex or null if it is not set
     *
     * @return string|null
     */
    public function getSex()
    {
        if (isset($this->response['gender']) && in_array($this->response['gender'], array('male', 'female'))) {
            return $this->response['gender'];
        }

        return null;
    }

    /**
     * Authenticate and return bool result of authentication
     *
     * @return bool
     */
    public function authenticate()
    {
        if (isset($_GET['code'])) {
            $params = array(
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'grant_type'    => 'authorization_code',
                'code'          => $_GET['code']
            );

            $tokenInfo = $this->post('https://accounts.google.com/o/oauth2/token', $params);

            if (isset($tokenInfo['access_token'])) {
                $params['access_token'] = $tokenInfo['access_token'];

                $userInfo = $this->get('https://www.googleapis.com/oauth2/v1/userinfo', $params);
                if (isset($userInfo['id']))
                {
                    $this->parseUserData($userInfo);

                    if (isset($this->response['birthday'])) {
                        $birthDate = explode('-', $this->response['birthday']);
                        $this->userInfo['birthDay']   = isset($birthDate[2]) ? $birthDate[2] : null;
                        $this->userInfo['birthMonth'] = isset($birthDate[1]) ? $birthDate[1] : null;
                        $this->userInfo['birthYear']  = isset($birthDate[0]) ? $birthDate[0] : null;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Prepare params for authentication url
     *
     * @return array
     */
    public function prepareAuthParams()
    {
        return array(
            'auth_url'    => 'https://accounts.google.com/o/oauth2/auth',
            'auth_params' => array(
                'redirect_uri'  => $this->redirectUri,
                'response_type' => 'code',
                'client_id'     => $this->clientId,
                'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile'
            )
        );
    }
}