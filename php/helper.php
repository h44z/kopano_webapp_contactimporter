<?php
/**
 * helper.php, Kopano Webapp contact to vcf im/exporter
 *
 * Author: Christoph Haas <christoph.h@sprinternet.at>
 * Copyright (C) 2012-2016 Christoph Haas
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */


namespace contactimporter;


class Helper
{
    /**
     * Generates a random string with variable length.
     *
     * @param $length the lenght of the generated string, defaults to 6
     * @return string a random string
     */
    public static function randomstring($length = 6)
    {
        // $chars - all allowed charakters
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

        srand((double)microtime() * 1000000);
        $i = 0;
        $pass = "";
        while ($i < $length) {
            $num = rand() % strlen($chars);
            $tmp = substr($chars, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }
        return $pass;
    }

    /**
     * respond/echo JSON
     *
     * @param $arr
     * @return string JSON encoded string
     */
    public static function respondJSON($arr)
    {
        echo json_encode($arr);
    }
}