<?php
use ViewerNodeNamespace\ViewerNodeSocket;

require dirname(__DIR__) . '\ViewerNodeProgram/vendor/autoload.php';

function find_free_port() {
    $sock = socket_create_listen(0);
    socket_getsockname($sock, $addr, $port);
    socket_close($sock);

    return $port;
}

function responseClient($port) {
    ignore_user_abort(true);
    set_time_limit(0);

    ob_start();
    ?>
    <!doctype html>
    <html lang="ru">
        <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
            <link rel="stylesheet" href="assets/css/style.css">
            <script src="https://use.fontawesome.com/dad5b28ae3.js"></script>
            <title>Узел представления SCADA-проектов</title>
        </head>
        <body>
            <div id="projectSidebar" class="sidebar">
                <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
                <div class="scada-project-info"></div>
                <div class="scada-project-struct"></div>
            </div>

            <div id="workplace" class="w-100 h-100">
                <button class="openbtn" onclick="openNav()">Меню SCADA-проекта</button>
                <div class="frame w-100 h-100"">
            </div>
            </div>

            <div class="modal fade" id="viewerNodeModal" tabindex="-1" role="dialog" aria-labelledby="viewerNodeModalTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Инициализация SCADA-проекта</h5>
                        </div>
                        <div class="modal-body">
                        </div>
                    </div>
                </div>
            </div>

            <script>
                var port = <?=$port?>;
            </script>
            <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
            <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
            <script src="assets/js/viewerNode.js"></script>
        </body>
    </html>
    <?
    header('Connection: close');
    header('Content-Length: '.ob_get_length());
    ob_end_flush();
    ob_flush();
    flush();
}

$port = find_free_port();
responseClient($port);
new ViewerNodeSocket($port);

?>
