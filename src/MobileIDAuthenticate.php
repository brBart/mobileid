<?php
/**
 * @copyright 2017 Kullar Kert
 * @license   https://opensource.org/licenses/MIT MIT
 */


namespace kullar84\MobileID;

use Session;

/**
 * Authentication provider for Mobile ID
 *
 * @author    Kullar Kert <kullar.kert@gmail.com>
 * @license   https://opensource.org/licenses/MIT MIT
 * @package   kullar84\MobileID
 * @copyright 2017 Kullar Kert
 */
class MobileIDAuthenticate
{


    /**
     * Dev mode
     *
     * @var bool
     */
    public $dev = false;

    /**
     * Service name
     *
     * @var string
     */
    public $serviceName = null;

    /**
     * Service message
     *
     * @var string
     */
    public $serviceMessage = '';

    /**
     * Service language
     *
     * @var string
     */
    public $serviceLanguage = 'EST';

    /**
     * SP Challange
     *
     * @var string
     */
    private $spChallenge = null;

    /**
     * Translations
     *
     * @var array
     */
    private $translations = [];

    /**
     * MobileIDAuthenticate constructor.
     *
     * @param string $name    Service name
     * @param string $message Service message
     * @param bool   $dev     Development mode
     *
     * @throws MobileIDException
     */
    public function __construct($dev = false)
    {
        $this->initMobileID($dev);
        $this->loadTranslations();
    }

    public function initMobileID($dev = false)
    {
        // Dev mode
        if ($dev) {
            $this->dev = $dev;
        } else {
            $this->dev = config('mobileid.dev');
        }

        // Service name
        $this->serviceName = config('mobileid.serviceName');

        // Language
        $this->serviceLanguage = config('mobileid.lang');

        // Default service message
        $this->serviceMessage = config('mobileid.serviceMessage');
    }

    /**
     * Set service name
     *
     * @param string $name Service name
     */
    public function setServiceName($name)
    {
        $this->serviceName = $name;
    }

    /**
     * Set service message
     *
     * @param string $message Message
     */
    public function setServiceMessage($message)
    {
        $this->serviceMessage = $message;
    }

    /**
     * Set service language
     *
     * @param string $lang Language
     */
    public function setLanguage($lang)
    {
        $this->serviceLanguage = $lang;

        $this->loadTranslations();

    }

    private function loadTranslations()
    {
        $_arrMap = [
            'EST' => 'et',
            'ENG' => 'en',
        ];

        $path = __DIR__.'/locale/'.$_arrMap[$this->serviceLanguage].'.php';

        if (file_exists($path)) {
            $this->translations = require($path);
        } else {
            throw new MobileIDException("Translation not found!");
        }
    }

    /**
     * Init SOAP client
     *
     * @return \SoapClient
     */
    private function initSoapClient()
    {
        // SOAP options
        $streamOptions = array(
            'http' => array(
                'user_agent' => 'PHPSoapClient',
            ),
        );
        $streamContext = stream_context_create($streamOptions);
        $soapOptions = array(
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'stream_context' => $streamContext,
            'trace' => true,
            'encoding' => 'utf-8',
            'classmap' => array(
                array(
                    'MobileAuthenticateResponse' => 'MobileAuthenticateResponse',
                ),
            ),
        );

        // Init SOAP client
        if ($this->dev) {
            $WSDL = 'https://tsp.demo.sk.ee/dds.wsdl';
            $this->spChallenge = '00000000000000000000';
            $this->serviceName = 'Testimine'; //must be
        } else {
            $WSDL = 'https://digidocservice.sk.ee/?wsdl';
            $this->spChallenge = '00000010000002000040';
        }

        $soapClient = new \SoapClient($WSDL, $soapOptions);

        return $soapClient;
    }

    /**
     * Start Mobile ID authentication
     *
     * @param string $phoneNo Phone number (372xxxxxxx)
     *
     * @throws MobileIDException
     *
     * @return AuthenticateResponse
     */
    public function startAuth(string $phoneNo)
    {
        try {
            // Remove leading country code and add country code if needed
            $phoneNo = $this->fixPhoneNo($phoneNo);

            // Init SOAP client
            $soapClient = $this->initSoapClient();

            // Make request
            $soapResponse = $soapClient->MobileAuthenticate(
                '',
                '',
                $phoneNo,
                $this->serviceLanguage,
                $this->serviceName,
                $this->serviceMessage,
                $this->spChallenge,
                'asynchClientServer',
                null,
                true,
                false
            );

            // Success
            if (!empty($soapResponse['UserIDCode']) && !empty($soapResponse['Sesscode']) && !empty($soapResponse['ChallengeID'])) {
                // Save Mobiil-ID data to session
                session()->put('mobileid.authenticate.response', serialize($soapResponse));
                session()->put('mobileid.authenticate.sesscode', strval($soapResponse['Sesscode']));

                // Return response
                $response = new AuthenticateResponse();
                $response->status = 'pending';
                $response->challengeResponse = $soapResponse['ChallengeID'];

                return $response;
            } else { // Fail
                throw new MobileIDException('Error talking to the Mobile ID service'.($this->dev ? ': '.print_r($soapResponse, 1) : ''));
            }
        } catch (\SoapFault $soapFault) {
            if (!empty($soapFault->detail->message)) {
                throw new MobileIDException(strval($soapFault->detail->message));
            } else {
                throw new MobileIDException('Error talking to the Mobile ID service'.($this->dev ? ': '.print_r($soapFault, 1) : ''));
            }
        }
    }

    /**
     * Check Mobile ID auth status
     *
     * @throws MobileIDException
     *
     * @return AuthenticateResponse
     */
    public function checkAuthStatus()
    {
        try {
            // Check
            $midResponse = unserialize(session()->get('mobileid.authenticate.response'));
            $sessCode = session()->get('mobileid.authenticate.sesscode');

            if (empty($midResponse['UserIDCode'])) {
                throw new MobileIDException('Error reading session data. Please try again to login');
            }
            if (empty($sessCode)) {
                throw new MobileIDException('Error reading session data. Please try again to login');
            }

            // Init SOAP client
            $soapClient = $this->initSoapClient();

            // Make request
            $soapResponse = $soapClient->GetMobileAuthenticateStatus(
                $sessCode,
                false
            );

            // Success
            $success = false;
            if (!empty($soapResponse['Status'])) {
                switch (strval($soapResponse['Status'])) {
                    // In progress
                case 'OUTSTANDING_TRANSACTION':
                    $response = new AuthenticateResponse();
                    $response->status = 'pending';
                    break;

                    // Success
                case 'USER_AUTHENTICATED':
                    session()->forget('mobileid.authenticate.response');
                    session()->forget('mobileid.authenticate.sesscode');

                    $response = new AuthenticateResponse();
                    $response->status = 'success';
                    $response->firstName = $midResponse['UserGivenname'];
                    $response->lastName = $midResponse['UserSurname'];
                    $response->idCode = $midResponse['UserIDCode'];
                    break;

                    // Error
                default:
                    session()->forget('mobileid.authenticate.response');
                    session()->forget('mobileid.authenticate.sesscode');

                    $response = new AuthenticateResponse();
                    $response->status = 'error';
                    switch (strval($soapResponse['Status'])) {
                    case 'MID_NOT_READY':
                        #$response->error = trans('mobileid::mobileid.MID_NOT_READY'); //Needs to be tested
                        $response->error = $this->translations['MID_NOT_READY'];
                        break;
                    case 'SENDING_ERROR':
                        $response->error = $this->translations['SENDING_ERROR'];
                        break;
                    case 'USER_CANCEL':
                        $response->error = $this->translations['USER_CANCEL'];
                        break;
                    case 'INTERNAL_ERROR':
                        $response->error = $this->translations['INTERNAL_ERROR'];
                        break;
                    case 'SIM_ERROR':
                        $response->error = $this->translations['SIM_ERROR'];
                        break;
                    case 'PHONE_ABSENT':
                        $response->error = $this->translations['PHONE_ABSENT'];
                        break;
                    default:
                        $response->error = $this->translations['UNKNOWN_ERROR'];
                        break;
                    }
                    break;
                }

                return $response;
            } else {
                throw new MobileIDException('Error talking to the Mobile ID service');
            }
        } catch (\SoapFault $soapFault) {
            if (!empty($soapFault->detail->message)) {
                throw new MobileIDException(strval($soapFault->detail->message));
            } else {
                throw new MobileIDException('Error talking to the Mobile ID service'.($this->dev ? ': '.print_r($soapFault, 1) : ''));
            }
        }
    }

    /**
     * Fixes phone number
     *
     * @param string $phoneNo Phone number
     *
     * @return string Fixed phone nr
     */
    private function fixPhoneNo($phoneNo)
    {
        $phoneNo = preg_replace('/^\+/', '', $phoneNo);
        if ($phoneNo && substr($phoneNo, 0, 2) != '37') {
            $phoneNo = '372'.$phoneNo;
        }

        return $phoneNo;
    }


}