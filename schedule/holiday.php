<?php
/*
 * kagen.yu
  文字コード UTF-8
*/
require_once('../application/loader.php');
$view->heading('祝日の設定');
?>
<h1>祝日の設定</h1>
<?=$view->error($hash['error'])?>
<div class="topcontentfolder">
	<div class="toplist">
		<div class="topcaption">
			<ul>
				<li>
				<form method="post" action="">
    				<a href="javascript:void(0)" onclick="parentNode.submit();">祝日を取得</a>
    				<input type="hidden" name="mode" value="google"/>
				</form>
				</li>
			</ul>
		<div class="clearer"></div>
		</div>
		<table class="toptimecard" style="border-spacing:0;border-collapse:collapse;">
			<tr>
				<th style="width: 40%;">日付</th>
				<th style="width: 50%;">祝日名称</th>
				<th style="border-right:0px; width:10%;">操作</th>
			</tr>
			<form class="content" method="post" action="">
			<tr <?=((is_array($hash['list']) && count($hash['list']) > 0)?'style="border-bottom: solid 1px grey;"':'')?>>
				<td><input type="text" name="holiday_date" id="holiday_date" style="width: 95%;"/></td>
				<td><input type="text" name="holiday_desc" id="holiday_desc" style="width: 95%;"/></td>
				<td><input type="submit" value="　追加　" /><input type="hidden" name="mode" value="add" /></td>
			</tr>
			</form>
			<?php
			if (is_array($hash['list']) && count($hash['list']) > 0) {
				$i = 0;
				foreach ($hash['list'] as $row) {
					$i++;
					$oddRow = ($i % 2 == 0?true:false);
					?>
						<tr <?=($oddRow?'':'class="bg_whiteSmoke"')?>>
							<td><?=$row['holiday_date']?></td>
							<td><?=$row['holiday_desc']?></td>
							<td>
								<form class="content right" method="post" action="">
									<input type="hidden" name="mode" value="delete" />
									<input type="hidden" name="holiday_date" value="<?=$row['holiday_date']?>" />
									<input type="submit" value="　削除　" />
								</form>
							</td>
						</tr>
			<?php
				}
			}
			?>
		</table>
	</div>
</div>
<?php
$view->footing();
?>
<script type="text/javascript">
$('#holiday_date').datetimepicker({
	timepicker:false,
	lang:"ja",
	format:'Y-m-d'
});
</script>
