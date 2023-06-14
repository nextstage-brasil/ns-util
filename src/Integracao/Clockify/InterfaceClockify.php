<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace NsUtil\Integracao\Clockify;

/**
 *
 * @author crist
 */
interface InterfaceClockify {

    public function setByName(string $name);

    public function setById(string $id);

    public function create($name);
}
