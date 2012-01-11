<?php

/*
	Load all of the gears classes at once, and asap
	This is typically the main file included into your boot.php file:
*/

require_once($_SERVER['GEARS_ROOT']."/lib/ErrorHandler.php");
require_once($_SERVER['GEARS_ROOT']."/lib/ErrorAccess.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Config.php");
require_once($_SERVER['GEARS_ROOT']."/lib/AutoLoader.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Controller.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Session.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Log.php");
require_once($_SERVER['GEARS_ROOT']."/lib/ApplicationEntryPoint.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Migrator.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Migration.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Model.php");
require_once($_SERVER['GEARS_ROOT']."/lib/RemoteModel.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Processor.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Process.php");
require_once($_SERVER['GEARS_ROOT']."/lib/ProcessRun.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Dispatcher.php");
require_once($_SERVER['GEARS_ROOT']."/lib/View.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Helper.php");
require_once($_SERVER['GEARS_ROOT']."/lib/UrlHelper.php");
require_once($_SERVER['GEARS_ROOT']."/lib/StringHelper.php");
require_once($_SERVER['GEARS_ROOT']."/lib/TagHelper.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Smarty/Smarty.class.php");
require_once($_SERVER['GEARS_ROOT']."/lib/HtmlView.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Mailer.php");
require_once($_SERVER['GEARS_ROOT']."/lib/RemoteProcedureController.php");
require_once($_SERVER['GEARS_ROOT']."/lib/MacroEngine.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Routes.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Validate.php");
require_once($_SERVER['GEARS_ROOT']."/lib/RouteInfo.php");
require_once($_SERVER['GEARS_ROOT']."/lib/FileCSV.php");
require_once($_SERVER['GEARS_ROOT']."/lib/Paginator.php");

?>
