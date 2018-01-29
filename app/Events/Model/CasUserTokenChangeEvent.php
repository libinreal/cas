<?php

namespace App\Events\Model;

use Illuminate\Queue\SerializesModels;
use App\Models\CasUser;
use App\Models\CasServiceUser;

class CasUserTokenChangeEvent extends Event
{
    use SerializesModels;

    /**
     * @var UserModel
     */
    private $_beforeChangeToken;

    private $_afterChangeToken;

    /**
     * CasUserTokenChangeEvent constructor.
     * @param string $beforeChangeToken
     * @param string $afterChangeToken
     */
    public function __construct($beforeChangeToken, $afterChangeToken)
    {
        $this->_beforeChangeToken = $beforeChangeToken;
        $this->_afterChangeToken    = $afterChangeToken;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * @return Request
     */
    public function getBeforeChangeToken()
    {
        return $this->_beforeChangeToken;
    }

    /**
     * @return UserModel
     */
    public function getAfterChangeToken()
    {
        return $this->_afterChangeToken;
    }
}
