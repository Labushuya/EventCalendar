<?php
  // Debugging
  error_reporting(E_ALL);

  // Lege Zeitzone fest
  date_default_timezone_set("Europe/Berlin");

  // Binde Konfigurationsdateien ein
  require_once 'includes/config.inc.php';

  // Binde Konfigurationsdatei ein
  require_once 'includes/dbc.inc.php';

  // Binde Funktionsdateien ein
  require_once 'includes/functions.inc.php';

  // Prüfe Datenbankverbindung
  if($mysqli) {
    // Erstelle Arrays für spätere Nutzung
    if(!isset($eventData)) {
      $eventData = array();
    }

    if(!isset($blockedData)) {
      $blockedData = array();
    }

    if(!isset($blockedDays)) {
      $blockedDays = array();
    }

    // Array mit korrigierten Monaten
    $month =  array(
                 1 =>  0,
                 2 =>  1,
                 3 =>  2,
                 4 =>  3,
                 5 =>  4,
                 6 =>  5,
                 7 =>  6,
                 8 =>  7,
                 9 =>  8,
                10 =>  9,
                11 => 10,
                12 => 11
              );

    // Hole alle anstehenden Veranstaltungen
    $select = "SELECT `id`, `event`, `handler`, `description`, `location`, `start`, `end` FROM `zev_events` ORDER BY `id`";
    $result = mysqli_query($mysqli, $select);
    $numrow = mysqli_num_rows($result);

    if($numrow > 0) {
      while($getrow = mysqli_fetch_assoc($result)) {
        // Rufe Datensatz nur dann, sofern intakt
        if(
          preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $getrow['start']) &&
          preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $getrow['end'])
       ) {
          if(!isset($manualID)) {
            $manualID = 0;
          } else {
            $manualID++;
          }

          // Hole Veranstaltungsdaten
          $eventData[$manualID] = array(
                                    0 => $getrow['event'],
                                    1 => $getrow['handler'],
                                    2 => $getrow['description'],
                                    3 => $getrow['location']
                                  );

          // Hole Veranstaltungsbeginn
          $start = explode("-", $getrow['start']);
          $startYear = $start[0];
          $startMonth = ($start[1] > 0 ? (int)($start[1] - 1) : $start[1]);
          $startDay = (int)$start[2];
          $eventStartDate[$manualID] = $startYear . "," . $startMonth . "," . $startDay;

          // Hole Veranstaltungsende
          $end = explode("-", $getrow['end']);
          $endYear = $end[0];
          $endMonth = ($end[1] > 0 ? (int)($end[1] - 1) : $end[1]);
          $endDay = (int)$end[2];
          $eventEndDate[$manualID] = $endYear . "," . $endMonth . "," . $endDay;
        // Datensatz beschädigt
        } else {

        }
      }

      // Setze ID zurück
      $manualID = 0;
    }

    //  Hole alle Zeitnehmer Accounts
    $select = "SELECT `id`, `vname`, `nname`, `color` FROM `zev_accounts` ORDER BY `id`";
    $result = mysqli_query($mysqli, $select);
    $numrow = mysqli_num_rows($result);

    // Wurde welche gefunden, lade diese in Array
    if($numrow > 0) {
      while($getrow = mysqli_fetch_assoc($result)) {
        if(!isset($manualID)) {
          $manualID = 0;
        } else {
          $manualID++;
        }

        // Suche zugehörige, geblockte Tage
        $blockedData[$manualID] = array(
                                    0 => $getrow['id'],
                                    1 => $getrow['vname'] . $getrow['nname'],
                                    2 => $getrow['color']
                                  );

        $selectBD = "SELECT `zid`, `date` FROM `zev_participation` WHERE `zid` = " . $getrow['id'];
        $resultBD = mysqli_query($mysqli, $selectBD);
        $numrowBD = mysqli_num_rows($resultBD);

        if($numrowBD > 0) {
          while($getrowBD = mysqli_fetch_assoc($resultBD)) {
            $yDB = date('Y', $getrowBD['date']);
            $mDB = (int)(date('n', $getrowBD['date']) - 1);
            $dDB = date('j', $getrowBD['date']);
            $blockedDays[] = $yDB . ", " . $mDB . ", " . $dDB;
          }

          /*
          echo "<pre>";
          print_r($blockedDays);
          echo "</pre>";

          Array
          (
              [0] => 2020, 2, 11
              [1] => 2020, 2, 11
          )
          */

          $blockedData[$manualID] = array(
                                      3 => $blockedDays
                                    );
        } else {
          // Lege leeren Array Index an
          $blockedData[$manualID] = array(
                                      3 => 'none'
                                    );
        }
      }
    }
  // Verbindungsaufbau nicht möglich
  } else {
    $error =	"
              Swal.fire({
                allowOutsideClick: false,
                allowEscapeKey: false,
                type: 'question',
                title: 'Verbindungsfehler',
                text: 'Es konnte keine Verbindung zur Datenbank aufgebaut werden!',
                footer: '<span><em style=\"color:#b94a48;\">Fehlercode: initDBConnect</em></span>',
                showConfirmButton: true,
                confirmButtonText: '<i class=\"fas fa-redo\"></i>&emsp;Seite neu laden',
                confirmButtonColor: '#b94a48',
                backdrop:	`
                  #ede7da
                  center
                  no-repeat
                `
              }).then((result) => {
                if(result.value) {
                  location.href = \"./index.php\";
                }
              });
              ";
  }
?>
<!DOCTYPE html>
<html lang="de" class="no-js">
  <head>
    <title>Veranstaltungskalender</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="assets/css/jquery-ui.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.theme.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.structure.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-year-calender.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400,600,700">
    <link rel="stylesheet" href="assets/css/style.css">

    <script src="assets/js/modernizr.min.js"></script>
  </head>
  <body>
    <header>
  		<a id="cd-logo" href="#0">
        <img src="assets/img/cd-logo.svg" alt="Homepage">
      </a>
  		<nav id="cd-top-nav">
  			<ul>
  				<li><a href="#0">Tour</a></li>
  				<li><a href="#myModal" data-toggle="modal">Login</a></li>
  			</ul>
  		</nav>
  		<a id="cd-menu-trigger" href="#0">
        <span class="cd-menu-text">Menu</span>
        <span class="cd-menu-icon"></span>
      </a>
  	</header>
  	<main class="cd-main-content">
      <div id="content">
        <div id="calendar"></div>
        <table class="table table-sm table-hover table-bordered table-striped">
          <thead>
            <tr>
              <th scope="col" colspan="2">Legende</th>
            </tr>
            <tr>
              <th scope="col">Zeitnehmer</th>
              <th scope="col">Schwerpunkt</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">Oliver Schmidt</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Rupert Schmidt</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Peter Uhlig</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Sebastian Bott</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Sebastian Knoll</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Bastian Schmidt</th>
              <th>AS, MS, KS, KR, BS, KL</th>
            </tr>
            <tr>
              <th scope="row">Daniel Lutz</th>
              <th>AS, MS, KS, KR, BS, KL, EN, MC</th>
            </tr>
          </tbody>
        </table>
      </div>
  	</main> <!-- cd-main-content -->

  	<nav id="cd-lateral-nav">
      <ul class="cd-navigation">
  			<li class="item-has-children">
  				<a href="#0">Services</a>
  				<ul class="sub-menu">
  					<li><a href="#0">Brand</a></li>
  					<li><a href="#0">Web Apps</a></li>
  					<li><a href="#0">Mobile Apps</a></li>
  				</ul>
  			</li> <!-- item-has-children -->

  			<li class="item-has-children">
  				<a href="#0">Products</a>
  				<ul class="sub-menu">
  					<li><a href="#0">Product 1</a></li>
  					<li><a href="#0">Product 2</a></li>
  					<li><a href="#0">Product 3</a></li>
  					<li><a href="#0">Product 4</a></li>
  					<li><a href="#0">Product 5</a></li>
  				</ul>
  			</li> <!-- item-has-children -->

  			<li class="item-has-children">
  				<a href="#0">Stockists</a>
  				<ul class="sub-menu">
  					<li><a href="#0">London</a></li>
  					<li><a href="#0">New York</a></li>
  					<li><a href="#0">Milan</a></li>
  					<li><a href="#0">Paris</a></li>
  				</ul>
  			</li> <!-- item-has-children -->
  		</ul> <!-- cd-navigation -->

  		<ul class="cd-navigation cd-single-item-wrapper">
  			<li><a href="#0">Tour</a></li>
  			<li><a href="#myModal" data-toggle="modal">Login</a></li>
  			<li><a href="#0">Register</a></li>
  			<li><a href="#0">Pricing</a></li>
  			<li><a href="#0">Support</a></li>
  		</ul> <!-- cd-single-item-wrapper -->

  		<ul class="cd-navigation cd-single-item-wrapper">
  			<li><a class="current" href="#0">Journal</a></li>
  			<li><a href="#0">FAQ</a></li>
  			<li><a href="#0">Terms &amp; Conditions</a></li>
  			<li><a href="#0">Careers</a></li>
  			<li><a href="#0">Students</a></li>
  		</ul> <!-- cd-single-item-wrapper -->

  		<div class="cd-navigation socials">
  			<a class="cd-twitter cd-img-replace" href="#0">Twitter</a>
  			<a class="cd-github cd-img-replace" href="#0">Git Hub</a>
  			<a class="cd-facebook cd-img-replace" href="#0">Facebook</a>
  			<a class="cd-google cd-img-replace" href="#0">Google Plus</a>
  		</div> <!-- socials -->
  	</nav>

    <div class="modal modal-fade" id="event-modal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
              <span class="sr-only">Close</span>
            </button>
            <h4 class="modal-title">
              Event
            </h4>
          </div>
          <div class="modal-body">
            <input type="hidden" name="event-index">
            <form class="form-horizontal">
              <div class="form-group">
                <label for="min-date" class="col-sm-4 control-label">Name</label>
                <div class="col-sm-7">
                  <input name="event-name" type="text" class="form-control">
                </div>
              </div>
              <div class="form-group">
                <label for="min-date" class="col-sm-4 control-label">Location</label>
                <div class="col-sm-7">
                  <input name="event-location" type="text" class="form-control">
                </div>
              </div>
              <div class="form-group">
                <label for="min-date" class="col-sm-4 control-label">Dates</label>
                <div class="col-sm-7">
                  <div class="input-group input-daterange" data-provide="datepicker">
                    <input name="event-start-date" type="text" class="form-control" value="2012-04-05">
                    <span class="input-group-addon">to</span>
                    <input name="event-end-date" type="text" class="form-control" value="2012-04-19">
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="save-event">
              Save
            </button>
          </div>
        </div>
      </div>
    </div>

    <div id="myModal" class="modal fade">
      <div class="modal-dialog modal-login">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Member Login</h4>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          </div>
          <div class="modal-body">
            <form action="/examples/actions/confirmation.php" method="post">
              <div class="form-group">
                <i class="fa fa-user"></i>
                <input type="text" class="form-control" placeholder="Username" required="required">
              </div>
              <div class="form-group">
                <i class="fa fa-lock"></i>
                <input type="password" class="form-control" placeholder="Password" required="required">
              </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block btn-lg" value="Login">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <a href="#">Forgot Password?</a>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery-ui.min.js"></script>
    <script src="assets/js/jquery.migrate.min.js"></script>
    <script src="assets/js/sweetalert2.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/bootstrap-year-calender.min.js"></script>
    <script src="assets/js/bootstrap-year-calender-de.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script>
      
      var currentYear = new Date().getFullYear();
      var redDateTime = new Date(currentYear, 2, 13).getTime();
      var circleDateTime = new Date(currentYear, 1, 20).getTime();
      var borderDateTime = new Date(currentYear, 0, 12).getTime();

      $(document).ready(function() {
        // Gebe mögliche Fehlermeldung aus
        <?php echo ($error !== "" ? $error : ""); ?>

        // Datepicker
        $('.datepicker').datepicker({
          language: 'de',
          calendarWeeks: true,
          format: 'dd.mm.yyyy'
        });

        // BS-Calendar
        $('#calendar').calendar({
          language: 'de',
          style: 'background',
          displayWeekNumber: true,
          enableContextMenu: true,
          enableRangeSelection: true,
          contextMenuItems:[
            {
                text: 'Update',
                click: editEvent
            },
            {
                text: 'Delete',
                click: deleteEvent
            }
          ],
          selectRange: function(e) {
            editEvent({
              startDate: e.startDate,
              endDate: e.endDate
            });
          },
          mouseOnDay: function(e) {
            if(e.events.length > 0) {
              var content = '';

              for(var i in e.events) {
                content += '<div class="event-tooltip-content">'
                         + '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>'
                         + '<div class="event-location">' + e.events[i].location + '</div>'
                         + '</div>';
              }

              $(e.element).popover({
                trigger: 'manual',
                container: 'body',
                html: true,
                content: content
              });

              $(e.element).popover('show');
            }
          },
          mouseOutDay: function(e) {
            if(e.events.length > 0) {
              $(e.element).popover('hide');
            }
          },
          dayContextMenu: function(e) {
            $(e.element).popover('hide');
          },
          // Zeitnehmer-Farben
          customDayRenderer: function(element, date) {
            if(date.getTime() <= redDateTime) {
                $(element).css('font-weight', 'bold');
                $(element).css('font-size', '15px');
                $(element).css('color', 'green');
            }
            else if(date.getTime() == circleDateTime) {
                $(element).css('background-color', 'red');
                $(element).css('color', 'white');
                $(element).css('border-radius', '15px');
            }
            else if(date.getTime() == borderDateTime) {
                $(element).css('border', '2px solid blue');
            }
          }
          <?php
            if(
              count($eventStartDate) == count($eventEndDate) &&
              count($eventStartDate) > 0 &&
              count($eventEndDate) > 0
           ) {
              echo	",
          dataSource: [
                    ";

              for($i = 0; $i < count($eventStartDate); $i++) {
                if($i < (count($eventStartDate) - 1)) {
                  $komma = ",";
                } else {
                  $komma = "";
                }

                if(!isset($eventData[$i][0])) {
                  $eventData[$i][0] = "";
                }

                if(!isset($eventData[$i][3])) {
                  $eventData[$i][3] = "";
                }

                echo	"
            {
              id: " . $i . ",
              name: '" . $eventData[$i][0] . "',
              location: '" . $eventData[$i][3] . "',
              startDate: new Date(" . $eventStartDate[$i] . "),
              endDate: new Date(" . $eventEndDate[$i] . ")
            }" . $komma . "
                      ";
              }

              echo	"
          ]
                    ";
            }
          ?>
        });

        $('#save-event').click(function() {
          saveEvent();
        });

        // Menu
        var $lateral_menu_trigger = $('#cd-menu-trigger'),
        		$content_wrapper = $('.cd-main-content'),
        		$navigation = $('header');

      	// Open-close lateral menu clicking on the menu icon
      	$lateral_menu_trigger.on('click', function(event) {
      		event.preventDefault();

      		$lateral_menu_trigger.toggleClass('is-clicked');
      		$navigation.toggleClass('lateral-menu-is-open');

          $content_wrapper.toggleClass('lateral-menu-is-open').one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function() {
      			// firefox transitions break when parent overflow is changed, so we need to wait for the end of the trasition to give the body an overflow hidden
      			$('body').toggleClass('overflow-hidden');
      		});

      		$('#cd-lateral-nav').toggleClass('lateral-menu-is-open');

      		// Check if transitions are not supported - i.e. in IE9
      		if($('html').hasClass('no-csstransitions')) {
      			$('body').toggleClass('overflow-hidden');
      		}
      	});

      	// Close lateral menu clicking outside the menu itself
      	$content_wrapper.on('click', function(event) {
      		if(!$(event.target).is('#cd-menu-trigger, #cd-menu-trigger span')) {
      			$lateral_menu_trigger.removeClass('is-clicked');
      			$navigation.removeClass('lateral-menu-is-open');

            $content_wrapper.removeClass('lateral-menu-is-open').one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function() {
      				$('body').removeClass('overflow-hidden');
      			});

      			$('#cd-lateral-nav').removeClass('lateral-menu-is-open');

            // Check if transitions are not supported
      			if($('html').hasClass('no-csstransitions')) {
      				$('body').removeClass('overflow-hidden');
      			}
      		}
      	});

      	// Open (or close) submenu items in the lateral menu. Close all the other open submenu items.
      	$('.item-has-children').children('a').on('click', function(event) {
          event.preventDefault();
      		$(this).toggleClass('submenu-open').next('.sub-menu').slideToggle(200).end().parent('.item-has-children').siblings('.item-has-children').children('a').removeClass('submenu-open').next('.sub-menu').slideUp(200);
      	});
      });

      /*
       * Documentation:
       * https://www.bootstrap-year-calendar.com/
       */
      function editEvent(event) {
        $('#event-modal input[name="event-index"]').val(event ? event.id : '');
        $('#event-modal input[name="event-name"]').val(event ? event.name : '');
        $('#event-modal input[name="event-location"]').val(event ? event.location : '');
        $('#event-modal input[name="event-start-date"]').datepicker('update', event ? event.startDate : '');
        $('#event-modal input[name="event-end-date"]').datepicker('update', event ? event.endDate : '');
        $('#event-modal').modal();
      }

      function deleteEvent(event) {
        var dataSource = $('#calendar').data('calendar').getDataSource();

        for(var i in dataSource) {
          if(dataSource[i].id == event.id) {
            dataSource.splice(i, 1);
            break;
          }
        }

        $('#calendar').data('calendar').setDataSource(dataSource);
      }

      function saveEvent() {
        var event = {
          id: $('#event-modal input[name="event-index"]').val(),
          name: $('#event-modal input[name="event-name"]').val(),
          location: $('#event-modal input[name="event-location"]').val(),
          startDate: $('#event-modal input[name="event-start-date"]').datepicker('getDate'),
          endDate: $('#event-modal input[name="event-end-date"]').datepicker('getDate')
        }

        var dataSource = $('#calendar').data('calendar').getDataSource();

        if(event.id) {
          for(var i in dataSource) {
            if(dataSource[i].id == event.id) {
              dataSource[i].name = event.name;
              dataSource[i].location = event.location;
              dataSource[i].startDate = event.startDate;
              dataSource[i].endDate = event.endDate;
            }
          }
        } else {
          var newId = 0;

          for(var i in dataSource) {
            if(dataSource[i].id > newId) {
              newId = dataSource[i].id;
            }
          }

          newId++;
          event.id = newId;

          dataSource.push(event);
        }

        $('#calendar').data('calendar').setDataSource(dataSource);
        $('#event-modal').modal('hide');
      }
    </script>
  </body>
</html>
