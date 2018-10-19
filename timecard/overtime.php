<?php
/*
 * kagen.yu
 * 文字コード UTF-8
 */
require_once('../application/loader.php');
?>
<?php if ($hash['result'] == "requested"): ?>
<a id="<?=$hash['overtime_date']?>_overtime" href="javascript:void(0);" ondblclick="inputOvertime(this)"><?=$hash['overtime_requested']?></a><input id="<?=$hash['overtime_date']?>_overtime_inp" onchange="postOvertime(this);" onblur="convertTime(this);" name="overtime_time_requested:<?=$hash['overtime_date']?>" style="width: 40px; height: 12px; display: none; ime-mode: inactive;" size="5" placeholder="00：00" disabled="disabled" data-otstatus="<?=$hash['overtime_date']?>_overtime_col" class="otinput" value="<?=$hash['overtime_requested']?>" /><span id="<?=$hash['overtime_date']?>_ot_stat"><img class="inlineicon" title="<?=$hash['overtime_updated']?>" src="../images/request.png"></span>
<?php elseif ($hash['result'] == "deleted"): ?>
<a id="<?=$hash['overtime_date']?>_overtime" href="javascript:void(0);" onclick="inputOvertime(this);">＋</a><input id="<?=$hash['overtime_date']?>_overtime_inp" onchange="postOvertime(this);" onblur="convertTime(this);" name="overtime_time_requested:<?=$hash['overtime_date']?>" style="width: 40px; height: 12px; display: none; ime-mode: inactive;" size="5" placeholder="00：00" disabled="disabled" data-otstatus="<?=$hash['overtime_date']?>_overtime_col" class="otinput" /><span id="<?=$hash['overtime_date']?>_ot_stat"></span>
<?php endif ?>