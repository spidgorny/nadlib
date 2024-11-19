<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.01.2016
 * Time: 14:59
 *
 * It's currently identical to HTMLFormFieldInterface
 * but Field is bigger than Type.
 * Type knows how to render <input type="text">
 * But Field knows how to wrap it with <tr><td><label>
 */
interface HTMLFormTypeInterface extends HTMLFormFieldInterface
{
}
