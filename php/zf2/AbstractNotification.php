<?php
namespace ReclogAPI\Model\Notification;
use ReclogAPI\Model\Event\AbstractEvent;
use ReclogAPI\Model\Notification;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractNotification
{
    use ServiceLocatorAwareTrait;
    use \ReclogAPI\Model\Service\LoggerTrait;


    /**
     * @var AbstractEvent
     */
    public $event;

    /**
     * @var \ReclogAPI\Model\Notification
     */
    public $notificationDbObject;

    /**
     * @var \ReclogAPI\Model\User
     */
    public $toUser;
    /**
     * @var \ReclogAPI\Model\User
     */
    public $fromUser;

    /**
     * @var \ReclogAPI\Model\NotificationCollection
     */
    public $notificationCollection;

    public $runAgain = false;

    public $debugMode = true;//now forced true

    public function __construct(AbstractEvent $event,$toUser,$fromUser)
    {
        $this->setServiceLocator($event->getServiceLocator());
        $this->notificationCollection = $this->getServiceLocator()->get('ReclogAPI\Model\NotificationCollection');
        $this->event = $event;

        $this->toUser = $toUser;
        $this->fromUser = $fromUser;

        $this->logger = $this->createLogger('data/log/notification_'.$this->notificationName.'.log');
    }

    protected $logger;
    protected $loggerError;

    protected function _error($msg)
    {
        $this->loggerError = $this->createLogger('data/log/notification_'.$this->notificationName.'_error.log');//create error log first
        $this->logger->err($msg);
        $this->loggerError->err($msg);
    }
    protected function _debug($msg)
    {
        if($this->isDebugMode())
            $this->logger->debug($msg);
    }

    public function enableDebugMode()
    {
        $this->debugMode = true;
    }
    public function disableDebugMode()
    {
        $this->debugMode = false;
    }
    public function isDebugMode()
    {
        return $this->debugMode;
    }

    public function setRunAgain()
    {
        $this->runAgain = true;
    }

    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return \ReclogAPI\Model\User
     */
    public function getToUser()
    {
        return $this->toUser;
    }
    /**
     * @return \ReclogAPI\Model\User
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    public function getNamespaceFromOs($os)
    {
        switch ($os) {
            case 'ios':
                return 'apple';

            case 'android':
                return 'android';
        }
    }

    /**
     * first time? write document
     * runAgain? check db first
     * @return bool
     */
    public function canRun()
    {
        //runAgain? check db first
        if($this->runAgain){
            //get document
            $this->notificationDbObject = $this->notificationCollection->getNotification(
                $this->event->eventId, $this->getToUser()
            );

            //Notification already sent ? (fanOut 1)
            if($this->notificationDbObject === null)
                return false;

            //Notification in processing state (fanOut -1) ?
            if($this->notificationDbObject)
                return true;
        }

        //first time? write document (fanOut -1) and go
        $this->notificationDbObject = $this->notificationCollection->addNotification(
            $this->event->eventId, $this->getToUser(), $this->getFromUser(), $this->notificationName, $this->getNotificationData()
        );
        return true;

    }

    /**
     * run again or first time? already in processing state, nothing to change
     * @return bool
     */
    public function setIncomplete()
    {
        //run again or first time? already in processing state, nothing to change
        return true;
    }

    /**
     * run again or first time? close document log already present
     * @return bool
     */
    public function finish()
    {
        //run again or first time? close document log already present
        return $this->notificationDbObject->finish();
    }

    private function _labelRun()
    {
        return $this->runAgain ? 'sendAgain' : 'send';
    }

    abstract public function getNotificationData();
    /**
     * delivery notification logic
     * @return bool
     */
    public function send()
    {
        $this->_debug("AbstractNotification ".$this->_labelRun().':'.$this->notificationName);

        try {

        if(!$this->canRun()){
            $this->_debug("AbstractNotification cannot run...consider notification sent");
            return true;//consider notification sent
        }

        //$this->_debug("AbstractNotification notificationData:".print_r($this->getNotificationData(),true));

        $toUser = $this->getToUser();//notification target User
        $this->_debug("AbstractNotification ToUser:".$toUser->id);

        $isAllDelivered = true;//no uuids?

        //MOBILE CLIENTS delivery
        if(isset($toUser->deviceUuids)){
            $isAllDelivered = null;//any uuids?

            foreach ($toUser->deviceUuids as $uuidObject) {
                //foreach uuids $toUser

                //uuid null ?
                if($uuidObject['uuid'] == ''){
                    $isAllDelivered |= true;
                    continue;
                }

                //uuidOs == apple || android
                $os = $this->getNamespaceFromOs($uuidObject['os']);
                $this->_debug("AbstractNotification delivery:".$os);

                //@TODO skip android notification
                if($os == 'android'){
                    $isAllDelivered |= true;
                    continue;
                };

                $fanOutField = $os.'FanOut';

                //instantiate concrete notification object
                $className = '\ReclogAPI\Model\Notification\\'.ucfirst($os).'\\'.ucfirst($this->notificationName);
                $badge = 1+$this->notificationCollection->countUnreadNotifications($toUser);
                /* @var $notification \ReclogAPI\Model\Notification\Apple\AbstractApns */
                $notification = new $className($this,$uuidObject['uuid'],$badge);//abstractNotification,deviceToken
                $isDelivered = $notification->send();

                //mark delivery field flag fanout done
                if($isDelivered)
                    $this->notificationDbObject->{$fanOutField} = Notification::FANOUT_DONE;

                $isAllDelivered |= $isDelivered;// {null||true == true} enough one delivery true

                $this->_debug("AbstractNotification delivery of ".$os.' => '.$isDelivered);
            }

        }

        //EMAILS delivery
        /*
         * $notificationDbObject->emailFanOut = Notification::FANOUT_DONE;
         */

        if($isAllDelivered){
            $this->_debug('AbstractNotification isAllDelivered: close record ');
            return $this->finish();//close record
        }

        //fallback incomplete
        $this->_debug('AbstractNotification incomplete!!! ');
        $this->setIncomplete();
        return false;

        } catch (\Exception $e) {
            $this->_error($this->notificationName.' AbstractNotification Exception:'.(string)$e);
            $this->setIncomplete();
            return false;
        }

    }

}