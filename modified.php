<?php
// Compacting two other files (show_file and ajax_get_files) to provide it as a single script file
if(isset($_POST['action'])) {
	if($_POST['action'] == 'show_file') {
		function detect_encoding($string, $list) {
		 
		  foreach ($list as $item) {
		    $sample = iconv($item, $item, $string);
		    if (md5($sample) == md5($string))
		      return $item;
		  }
		  return null;
		}
		$exec = 'cat ' . $_POST['filename'];
		$array = array();
		$string = file_get_contents($_POST['filename']);
		$string = mb_convert_encoding($string, 'utf-8', detect_encoding($string, array('Windows-1251', 'UTF-8')));
		echo htmlspecialchars($string);
		die();
	} elseif($_POST['action'] == 'get_files') {
		set_time_limit(0);
		$dateFrom = null;
		if(isset($_POST['date_from'])) $dateFrom = $_POST['date_from'];
		$dateTo = null;
		if(isset($_POST['date_to'])) $dateTo = $_POST['date_to'];
		$dir = null;
		if(isset($_POST['dir'])) $dir = $_POST['dir'];

		$from = 0;
		$to = 0;

		if($dateFrom !== null) {
			$dateFromDate = strtotime($dateFrom);
			$from = floor((time() - $dateFromDate) / (60*60*24));
		}

		if($dateTo !== null) {
			$dateToDate = strtotime($dateTo);
			$to = floor((time() - $dateToDate) / (60*60*24));
		}

		$array = array();
		//$command = 'find ' . $dir . ' -type f -mtime -'.$from.' ! -mtime -'.$to.' -printf "%p;%TY-%Tm-%Td \n"';
		$command = 'find ' . $dir . ' -mtime -' . $from . ' ! -mtime -' . $to . ' -type f  \( -name "*php*" -o -name "*js" -o -name "*htm*" \) -printf "%p;%TY-%Tm-%Td\n" | grep -v ".*cache.*" | grep -v ".*wizard.*"';
		exec($command, $array);
		$files = array();
		$names = array();
		$dates = array();
		foreach ($array as $key => $value) {
			$files[$key] = explode(';', $value);
			// To sort the array by date
			$names[$key] = $files[$key][0];
			$dates[$key] = $files[$key][1];
		}
		array_multisort($dates, SORT_DESC, $names, SORT_ASC, $files);
		//echo 'Result: ' . print_r($array, true);
		$result = array(
			'command' => $command,
			'files' => $files
		);
		echo json_encode($result);
		die();
	}
}
?>
<!doctype html>
 
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Finding modified files</title>
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  <!--link rel="stylesheet" href="/resources/demos/style.css" /-->
  <script>
  $(function() {
    $( ".datepicker" ).datepicker();
    	var $files = $('#files'),
    		$fileLoading = $('#files-loading'),
    		$showFileLoading = $('#show-file-loading'),
    		$fileContents = $('#show-file'),
    		$command = $('#command');
	  $('#form').on('submit', function(){
	  		var date_from = $('#form #date_from').val(),
	  			date_to = $('#form #date_to').val(),
	  			dir = $('#form #dir').val();
	  		$fileLoading.removeClass('hidden');
	  		$files.html('');
			$fileContents.html('');
			$command.html('');
		  $.ajax({
		  	//url: "ajax_get_files.php",
		  	url: "",
		  	method: "POST",
		  	dataType: "json",
		  	data: {
		  		date_from: date_from,
		  		date_to: date_to,
		  		dir: dir,
		  		action: "get_files",
		  	}
		  }).done(function(data) {
		  	$command.html(data['command']);
		  	var html = '<ul>';
		  	for (var i = data['files'].length - 1; i >= 0; i--) {
		  		html += '<li><a href="#" class="file-link" data-filename="' + data['files'][i][0] + '">' + data['files'][i][0] + '</a> - ' + data['files'][i][1] + '</li>';
		  	};
		  	html += '</ul>';
		  	$files.html(html);
		  }).always(function(){
		  	$fileLoading.addClass('hidden');
		  });
		  return false;
		});
	  $(document).on('click', '.file-link', function(e) {
	  	e.preventDefault();
		$showFileLoading.removeClass('hidden');
		$fileContents.html('');
	  	$.ajax({
	  		//url: "show_file.php",
	  		url: "",
	  		method: "POST",
	  		data: {filename: $(this).data('filename'), action: "show_file"}
	  	}).done(function(data){
	  		console.log(data);
	  		$fileContents.html('<pre>' + data + '</pre>');
	  	}).always(function(){
	  		$showFileLoading.addClass('hidden');
	  	});
	  });
  });

  </script>
  <style>
  	/*.file-link {
  		text-decoration: underline;
  		color: blue;
  	}*/
  	.hidden {
  		display: none;
  	}
  	.loading {
  		width: 100px;
  		height: 50px;
  		background: rgba( 255, 255, 255, .8 ) 
                url('http://i.stack.imgur.com/FhHRx.gif') 
                50% 50% 
                no-repeat;
  	}
  	#files, #show-file {
  		max-height: 400px;
  		overflow: auto;
  	}
  	#form p {
  		line-height: 10px;
  	}
  </style>
</head>
<body>

<form method="get" id="form">
	<p>Date from: <input type="text" id="date_from" name="date_from" class="datepicker" /> to: <input type="text" id="date_to" name="date_to" class="datepicker" />
	 Dir: <input type="text" id="dir" name="dir">
	<input type="submit" value="Find files"></p>
 </form>

 <p id="command"></p>
 <div id="files"></div>
 <div id="files-loading" class="loading hidden"></div>

 <div id="show-file"></div>
 <div id="show-file-loading" class="loading hidden"></div>
 
</body>
</html>