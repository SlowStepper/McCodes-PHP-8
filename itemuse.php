<?php
/**
 * MCCodes Version 2.0.5b
 * Copyright (C) 2005-2012 Dabomstew
 * All rights reserved.
 *
 * Redistribution of this code in any form is prohibited, except in
 * the specific cases set out in the MCCodes Customer License.
 *
 * This code license may be used to run one (1) game.
 * A game is defined as the set of users and other game database data,
 * so you are permitted to create alternative clients for your game.
 *
 * If you did not obtain this code from MCCodes.com, you are in all likelihood
 * using it illegally. Please contact MCCodes to discuss licensing options
 * in this case.
 *
 * File: itemuse.php
 * Signature: 9633f89704acd2ba6a05feab908b636d
 * Date: Fri, 20 Apr 12 08:50:30 +0000
 */

require_once('globals.php');
$_GET['ID'] =
        (isset($_GET['ID']) && is_numeric($_GET['ID']))
                ? abs(intval($_GET['ID'])) : '';
if (!$_GET['ID'])
{
    echo 'Invalid use of file';
}
else
{
    $i =
            $db->query(
                    "SELECT `effect1`, `effect2`, `effect3`,
                     `effect1_on`, `effect2_on`, `effect3_on`,
                     `itmname`, `inv_itemid`
                     FROM `inventory` AS `iv`
                     INNER JOIN `items` AS `i`
                     ON `iv`.`inv_itemid` = `i`.`itmid`
                     WHERE `iv`.`inv_id` = {$_GET['ID']}
                     AND `iv`.`inv_userid` = $userid");
    if ($db->num_rows($i) == 0)
    {
        $db->free_result($i);
        echo 'Invalid item ID';
    }
    else
    {
        $r = $db->fetch_row($i);
        $db->free_result($i);
        if (!$r['effect1_on'] && !$r['effect2_on'] && !$r['effect3_on'])
        {
            echo 'Sorry, this item cannot be used as it has no effect.';
            die($h->endpage());
        }
        for ($enum = 1; $enum <= 3; $enum++)
        {
            if ($r["effect{$enum}_on"])
            {
                $einfo = unserialize($r["effect{$enum}"]);
                if ($einfo['inc_type'] == "percent")
                {
                    if (in_array($einfo['stat'],
                            ['energy', 'will', 'brave', 'hp']))
                    {
                        $inc =
                                round(
                                        $ir['max' . $einfo['stat']] / 100
                                                * $einfo['inc_amount']);
                    }
                    else
                    {
                        $inc =
                                round(
                                        $ir[$einfo['stat']] / 100
                                                * $einfo['inc_amount']);
                    }
                }
                else
                {
                    $inc = $einfo['inc_amount'];
                }
                if ($einfo['dir'] == "pos")
                {
                    if (in_array($einfo['stat'],
                            ['energy', 'will', 'brave', 'hp']))
                    {
                        $ir[$einfo['stat']] =
                                min($ir[$einfo['stat']] + $inc,
                                        $ir['max' . $einfo['stat']]);
                    }
                    else
                    {
                        $ir[$einfo['stat']] += $inc;
                    }
                }
                else
                {
                    $ir[$einfo['stat']] = max($ir[$einfo['stat']] - $inc, 0);
                }
                $upd = $ir[$einfo['stat']];
                if (in_array($einfo['stat'],
                        ['strength', 'agility', 'guard', 'labour', 'IQ']))
                {
                    $db->query(
                            "UPDATE `userstats`
                             SET `{$einfo['stat']}` = '{$upd}'
                             WHERE `userid` = {$userid}");
                }
                else
                {
                    $db->query(
                            "UPDATE `users`
                             SET `{$einfo['stat']}` = '{$upd}'
                             WHERE `userid` = {$userid}");
                }
            }
        }
        echo $r['itmname'] . ' used successfully!';
        item_remove($userid, $r['inv_itemid'], 1);
    }
}
$h->endpage();
