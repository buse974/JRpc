<?php

namespace Mock;

class Mock
{
    /**
     * @invokable
     */
    public function uneMethode()
    {
        return 'call_ok';
    }

    public function uneMethodeNoInvokable()
    {
    }
}
