<?php

class jQueryTmpl_Data_Factory
{
    public function createFromJson($json)
    {
        try
        {
            $obj = json_decode($json);
        }
        catch (Exception $e)
        {
            throw new jQueryTmpl_Data_Exception($e->getMessage());
        }

        if (!($obj instanceof stdClass))
        {
            throw new jQueryTmpl_Data_Exception('Could not create data object from JSON string');
        }

        return new jQueryTmpl_Data
        (
            $obj,
            new jQueryTmpl_Data_Factory()
        );
    }

    public function createFromArray(array $array)
    {
        return new jQueryTmpl_Data
        (
            json_decode
            (
                json_encode($array)
            ),
            new jQueryTmpl_Data_Factory()
        );
    }

    public function createFromStdClass(stdClass $obj)
    {
        return new jQueryTmpl_Data
        (
            $obj,
            new jQueryTmpl_Data_Factory()
        );
    }
}

