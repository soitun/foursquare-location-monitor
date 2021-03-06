<div class="row">

	<div class="span3">
		<label>Venue</label>
		<h4><i class="icon-map-marker"></i> <a href="<?php echo site_url('foursquare/venue') .'/'. $check->venue_id; ?>"><?php echo __($check->check_title); ?></a></h4>
	</div>
	
	<div class="span3">
		<label>Status</label>
		<div><?php if ($check->active == 1): ?>
		<h4><i class="icon-star"></i> Active</h4>
		<?php else: ?>
		<h4><i class="icon-star-empty"></i> Inactive</h4>
		<?php endif; ?></div>
	</div>
		
	<div class="span3">
		<label>Created</label>
		<h4><?php echo (!empty_date($check->insert_ts)) ? date('F j, Y', strtotime($check->insert_ts)) : '<span class="muted">N/A</span>'; ?></h4>
	</div>
	
</div>

<br />
	
<div class="row">

	<div class="span3">
		<label>Last Daily Check</label>
		<h4><?php echo (!empty_date($check->last_daily_check_ts)) ? date('F j, Y, g:i a', strtotime($check->last_daily_check_ts)) : '<span class="muted">-</span>'; ?></h4>
	</div>
	
	<div class="span3">
		<label>Last Live Check</label>
		<h4><?php echo (!empty_date($check->last_daily_check_ts)) ? date('F j, Y, g:i a', strtotime($check->last_live_check_ts)) : '<span class="muted">-</span>'; ?></h4>
	</div>
	
	<div class="span3">
		<label>&nbsp;</label>
		<p><a href="<?php echo site_url('checks/check_edit') .'/'. $check->id; ?>" class="btn small"><i class="icon-pencil"></i> Edit Check</a></p>
	</div>
	
</div>

<hr />

<div class="pull-right">
	<a href="<?php echo site_url('checks/export') .'/'. $check->id . '?type=live'; ?>" class="btn small" rel="tooltip" title="Export CSV"><i class="icon-download-alt"></i></a>
</div>
<h3>Live Data</h3>
<?php if (count($live_data) > 2): ?>
<div id="chart_live" class="spinner" style="width:100%; height:275px; margin-bottom:1em;">
	<p class="alert alert-info">Loading ...</p>
</div>
<?php else: ?>
<p class="alert alert-information">
	Live data can be viewed in about 15 minutes.
	<span class="close" onclick="$('.alert').hide();">&times</span>
</p>
<?php endif; ?>

<table class="table table-striped table-bordered table-rounded">
<thead>
<tr>
	<th>Date &amp; Time</th>
	<th>Friends Here</th>
	<th>Others Here</th>
	<th>Total Here</th>
</tr>
</thead>
<tbody>
<?php foreach ($live_data as $row): ?>
<tr>
	<td><?php echo date('n/j/Y h:i a', strtotime($row->insert_ts)); ?></td>
	<td><?php echo ($row->herenow_friends); ?></td>
	<td><?php echo ($row->herenow_other); ?></td>
	<td><?php echo ($row->herenow_total); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr />

<div class="pull-right">
	<a href="<?php echo site_url('checks/export') .'/'. $check->id . '?type=daily'; ?>" class="btn small" rel="tooltip" title="Export CSV"><i class="icon-download-alt"></i></a>
</div>
<h3>Daily Data</h3>
<?php if (count($daily_data_delta) > 2): ?>
<div id="chart_daily" class="spinner" style="width:100%; height:275px; margin-bottom:1em;">
	<p class="alert alert-info">Loading ...</p>
</div>
<?php else: ?>
<p class="alert alert-information">
	Daily data can be viewed in about 48 hours.
	<span class="close" onclick="$('.alert').hide();">&times</span>
</p>
<?php endif; ?>

<table class="table table-striped table-bordered table-rounded">
<thead>
<tr>
	<th>Date</th>
	<th>Total Checkins</th>
	<th>Unique Visited</th>
	<th>Tips Left</th>
	<th>Photos</th>
</tr>
</thead>
<tbody>
<?php foreach ($daily_data as $row): ?>
<tr>
	<td><?php echo date('n/j/Y', strtotime($row->log_date)); ?></td>
	<td><?php echo ($row->total_checkins); ?></td>
	<td><?php echo ($row->unique_visitors); ?></td>
	<td><?php echo ($row->tips_left); ?></td>
	<td><?php echo ($row->photo_count); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr />

<p>
	<a href="<?php echo site_url('checks'); ?>">&laquo; Back to Checks</a>
</p>

<?php include ('_chart_js.php'); ?>