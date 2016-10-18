<?php
//------------------------------------------------------------------------------
// Header
//------------------------------------------------------------------------------
function getHeader(){
    header("Content-type: text/html");
    echo '<!DOCTYPE html>
<html>
        <head>
                <title>'._('Service d\'Authentification Central de laclasse.com').'</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="mobile-web-app-capable" content="yes">
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
                <style>
body {
        color: white;
        background-color: #1aaacc;
    font-family: "Open Sans", sans-serif;
        font-size: 20px;
}

a {
        color: white;
}

.logo {
        width: 55%;
        opacity: 0.2;
        position: absolute;
        left: -5%;
        top: -5%;
        -webkit-user-select: none;
}

.footer {
    position: fixed;
    bottom: 1%;
        right: 0px;
        opacity: 0.3;
        width: 75%;
        height: 15%;
}

.footer center {
        width: 33%;
        height: 100%;
        float: left;
}

.footer img {
        width: 100%;
        height: 100%;
}

.btn {
        display: inline-block;
        font-size: 16px;
        text-transform: uppercase;
        padding: 10px 20px;
    border: 1px solid white;
    border-radius: 0;
        background-color: #5bc0de;
        margin: 5px;
        color: white;
    white-space: nowrap;
        text-decoration: none;
        cursor: pointer;
}

.btn:hover {
        background-color: rgba(91,192,222,0);
}

.box {
        margin: 20px;
        float: right;
        background: rgba(255,255,255,0.2);
        padding: 20px;
}

input[type=text], input[type=password] {
        height: 30px;
        border: 1px solid white;
        background-color: rgba(255,255,255,0.3);
        margin: 5px;
        color: white;
        font-size: 18px;
        padding-left: 10px;
        padding-right: 10px;
}

.title {
        font-weight: bold;
        margin-bottom: 20px;
}
                </style>
        </head>
        <body>
                <img draggable="false" class="logo" src="images/logolaclasse.svg" alt="Logo ENT">
                <div class="footer">
                        <center><img draggable="false" src="images/grandlyon-logo-blanc.svg" alt="Logo Métropole du Grand Lyon" /></center>
                        <center><img draggable="false" src="images/logo-academie-blanc.svg" alt="Logo Académie de Lyon" /></center>
                </div>
                <div style="position: absolute; top: 0px; left: 0px; right: 0px; bottom: 0px;">
                <center>
                <div style="max-width: 1200px">
                <div style="text-align: center; max-width: 400px; padding: 40px; padding-top: 150px; padding-bottom: 100px; float: left;">
                        <div style="font-weight: bold; font-size: 34px">Laclasse.com</div><br>
                        Espace Numérique de Travail<br>
                        des collèges et écoles de la Métropole de Lyon.

                        <p>
                          <strong>Besoin d\'aide ?</strong>
                          <ul style="text-align:left">
                            <li>
                              <a href="http://ent-laclasse.blogs.laclasse.com">Consulter le blog de l\'ENT</a>
                            </li>
<li>
si vous êtes parent, élève ou personnel contactez votre administrateur d\'établissement
</li>
                            <li>
                              si vous êtes administrateur d\'établissement sur le territoire de la Métropole de Lyon contactez le
                                                                 <a href="mailto:svp4356@grandlyon.com">SVP Métropole</a>
    </li>
    <li>
    sinon prenez contact avec votre collectivité de rattachement
                                                                </li>
    </ul>
    </p>
    </div>
    ';
}

?>
