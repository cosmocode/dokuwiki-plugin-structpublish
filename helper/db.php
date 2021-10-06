<?php

class helper_plugin_structpublish_db extends helper_plugin_struct_db
{
    /**
     * Overwrites dummy IS_PUBLISHER from struct plugin
     *
     * @return bool
     */
    public function IS_PUBLISHER() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $args = func_get_args();
        // FIXME real check
        // FIXME assignments have to exist!
//        if (array_key_exists($pid, self::$assignments)) {
        return true;
//        }

    }
}
