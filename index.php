<?php
/*##############################################################################
# Project:    Labels - Extremadura High Schools
# Purpose:    Module to generate labels of students for laptops
# Date:       24-Sep-2010.
# Ver.:       29-Sep-2010.
# Copyright:  2010 - Manu Mora Gordillo       <manuito @nospam@ gmail.com>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# 
############################################################################## */

	if($_POST["action"]=="generate_labels"){

		require('fpdf/fpdf.php');
		include('qr/qr_img.php');	

		// Conectamos con LDAP
		$user="cn=".$_POST["userLDAP"].",ou=People,dc=instituto,dc=extremadura,dc=es";

		$conex=ldap_connect("ldap://".$_POST["hostLDAP"], 389) or die ("No ha sido posible conectarse al servidor<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");
		  
		if (!@ldap_set_option($conex, LDAP_OPT_PROTOCOL_VERSION, 3))
			 die("Falló la configuracion de protocolo version 3<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");

	   if (!@ldap_bind($conex, $user, $_POST["password"]))
			die("Upsss, fall&oacute; la autenticaci&oacute;n. Vuelve a intentarlo otra vez.<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");

		// Obtenemos la info de LDAP
		$dn = "ou=Group,dc=instituto,dc=extremadura,dc=es";

		$filter="(|";
		for($i=0;$i<count($_POST["classroomGroup"]);$i++){
			$filter.="(cn=".$_POST["classroomGroup"][$i].")";
		}
		$filter.=")";

		$fields = array("cn","grouptype","memberuid");
		$sr=ldap_search($conex, $dn, $filter,$fields);
		$groups = ldap_get_entries($conex, $sr);

		$students = array();
		for($i=0;$i<count($groups);$i++){
			if($groups[$i]["grouptype"][0]=="school_class"){
				for($j=0;$j<count($groups[$i]["memberuid"]);$j++){
					if($groups[$i]["memberuid"][$j]!="")
						$students[] = $groups[$i]["memberuid"][$j];
				}
			}
		}

		if(count($students)=="0")
			die("No se han encontrado alumnos.<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");

		sort($students);

		$filter = "uid=*";
		
		if($_POST["filter"]!=""){
			// para que imprima la etiqueta en una posicion determinada de la pagina de etiquetas
                        //$offset = $_POST["offset"];
			//$num_label = $offset - 1;
			$array_filter = explode(",",$_POST["filter"]);

			$filter = "(|";
			for($r=0;$r<count($array_filter);$r++){
				$filter.="(uid=*".trim($array_filter[$r])."*)";
			}
			$filter.= ")";
			//$filter = $_POST["filter"]."*";
		}
		//echo ">>>>>>".$filter;
		$dn = "ou=People,dc=instituto,dc=extremadura,dc=es";
		$fields = array("uid","cn","employeenumber","jpegphoto");
		$sr=ldap_search($conex, $dn, $filter, $fields);
		//$sr=ldap_search($conex, $dn, "(|(uid=*marriaza*)(uid=*arodriguez*))", $fields);
		$students_tmp = ldap_get_entries($conex, $sr);

		// Llenar labels con posiciones vacias, para poner offset en etiquetas y comenzar a imprimir en una det. posicion
                $offset = $_POST["offset"];
		for ($j=1;$j<$offset;$j++)
                        $labels[] = array("cn"=>"","uid"=>"","nie"=>"","foto"=>"");

		for($i=0;$i<count($students_tmp);$i++){
			if(in_array($students_tmp[$i]["uid"][0],$students))
				$labels[] = array("cn"=>$students_tmp[$i]["cn"][0],"uid"=>$students_tmp[$i]["uid"][0],"nie"=>$students_tmp[$i]["employeenumber"][0],"foto"=>$students_tmp[$i]["jpegphoto"][0]);
				//echo ">>>>>>>>>>>>>>>>".$students_tmp[$i]["jpegphoto"][0];
		}

		// Creamos el PDF
		$pdf=new FPDF();
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',8);
	
		$num_rows = $_POST["rows"];
		$num_cols = $_POST["columns"];

		$margenX = $_POST["xMargin"];
		$margenY = $_POST["yMargin"];
		$ancho = $_POST["width"];
		$alto = $_POST["height"];

		$QR_size=$_POST["qrSize"];
		$photoWidth=$_POST["photoWidth"];
		$photoHeight=$_POST["photoHeight"];

		$num_label=0;
		if(!file_exists("tmp")){
			mkdir("tmp");
		}

		for($i=0;$i<$num_rows;$i++){
			for($j=0;$j<$num_cols;$j++){

				if(($num_label)==count($labels)){
					$pdf->Output();
					exit();
				}

				$x = $margenX + $j*$alto;
				$y = $margenY + $i*$ancho;

				// Para control de la posicion de inicio de impresion de las etiquetas
				if ($labels[$num_label]["cn"] != "") {
					$pdf->Text($x, $y+$QR_size+2, $labels[$num_label]["cn"]);

					if(isset($_POST["org"]))
						$pdf->Text($x, $y+$QR_size+5, $_POST["org"]." - ".$labels[$num_label]["uid"]);
					else
						$pdf->Text($x, $y+$QR_size+5, $labels[$num_label]["uid"]);

					$micro = explode(" ",microtime());
					$id = time().$micro[0];

					$student_data = 'BEGIN:VCARD'.urldecode("%0A");
					$student_data.= 'N:'.$labels[$num_label]["cn"].urldecode("%0A");
					$student_data.= 'ROLE:Student'.urldecode("%0A");
	
					if(isset($labels[$num_label]["nie"]))
						$student_data.= 'UID:'.$labels[$num_label]["nie"].urldecode("%0A");

					if(isset($_POST["org"]))
						$student_data.= 'ORG:'.$_POST["org"].urldecode("%0A");

					if(isset($_POST["address"]))
						$student_data.= 'ADR:'.$_POST["address"].urldecode("%0A");

					if(isset($_POST["phone"]))
						$student_data.= 'TEL:'.$_POST["phone"].urldecode("%0A");

					if(isset($_POST["email"]))
						$student_data.= 'EMAIL:'.$_POST["email"].urldecode("%0A");

					if(isset($_POST["web"]))
						$student_data.= 'URL:'.$_POST["web"].urldecode("%0A");

					$student_data.= 'END:VCARD';

					generateQRCode($student_data,"tmp/QR".$id.".png");		

					if(file_exists("tmp/QR".$id.".png")){
						$pdf->Image("tmp/QR".$id.".png",$x,$y,$QR_size);
						@unlink("tmp/QR".$id.".png");
					}

					if($labels[$num_label]["foto"]!=""){
						$photoStudent = "tmp/Alu".$id.".png";
			
						$im = @imagepng(@imagecreatefromstring($labels[$num_label]["foto"]),$photoStudent);

						if ($im !== false && file_exists($photoStudent))
							$pdf->Image($photoStudent, $x + $QR_size, $y, $photoWidth, $photoHeight);

						@unlink($photoStudent);
					}
				} 

				$num_label++;

				if(($i+1)==$num_rows && ($j+1)==$num_cols && ($num_label+1)<count($labels)){
					$pdf->AddPage();
					$i=0;
					$j=-1;
				}
			}
		}
		$pdf->Output();
		exit();
	} elseif($_POST["action"]=="generate_spreadsheet"){
		require_once('ods/ods.php');

		// Conectamos con LDAP
		$user="cn=".$_POST["userLDAP"].",ou=People,dc=instituto,dc=extremadura,dc=es";

		$conex=ldap_connect("ldap://".$_POST["hostLDAP"], 389) or die ("No ha sido posible conectarse al servidor<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");
		  
		if (!@ldap_set_option($conex, LDAP_OPT_PROTOCOL_VERSION, 3))
			 die("Falló la configuracion de protocolo version 3<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");

	   if (!@ldap_bind($conex, $user, $_POST["password"]))
			die("Upsss, fall&oacute; la autenticaci&oacute;n. Vuelve a intentarlo otra vez.<br><br><input type='button' value='Volver' onClick='location.href=\"index.php\"'/>");

		// Obtenemos la info de LDAP
		$dn = "ou=Group,dc=instituto,dc=extremadura,dc=es";

		$filter="(|";
		for($i=0;$i<count($_POST["classroomGroup"]);$i++){
			$filter.="(cn=".$_POST["classroomGroup"][$i].")";
		}
		$filter.=")";

		$fields = array("cn","grouptype","memberuid");
		$sr=ldap_search($conex, $dn, $filter,$fields);
		$groups = ldap_get_entries($conex, $sr);

		for($i=0;$i<count($groups);$i++){
			if($groups[$i]["grouptype"][0]=="school_class"){
				for($j=0;$j<count($groups[$i]["memberuid"]);$j++){
					if($groups[$i]["memberuid"][$j]!=""){
						$listGroups[$groups[$i]["cn"][0]][] = $groups[$i]["memberuid"][$j];
					}
				}
			}
		}

		ksort($listGroups);
		
		$filter = "";
		if($_POST["filter"]!="")
			$filter = $_POST["filter"]."*";

		$dn = "ou=People,dc=instituto,dc=extremadura,dc=es";
		$fields = array("uid","cn","employeenumber");
		$sr=ldap_search($conex, $dn, "uid=*".$filter, $fields);
		$students_tmp = ldap_get_entries($conex, $sr);
		
		$completeListGroups = array();
		foreach($listGroups as $key=>$value){
			sort($value);
			
			$students = array();
			for($i=0;$i<count($students_tmp);$i++){
				if(in_array($students_tmp[$i]["uid"][0],$value))
					$students[$students_tmp[$i]["uid"][0]] = array("class"=>$key,"cn"=>$students_tmp[$i]["cn"][0],"uid"=>$students_tmp[$i]["uid"][0],"nie"=>$students_tmp[$i]["employeenumber"][0]);
			}
			ksort($students);			
			$completeListGroups[$key] = $students;
		}

		$ods  = new ods();
		$ods->setPath2OdsFiles('ods');

		$table = new odsTable('table 1');

		foreach($completeListGroups as $key=>$group)
			foreach($group as $key2=>$list){
				$row   = new odsTableRow();			
				$row->addCell( new odsTableCellString($list["class"]) );
				$row->addCell( new odsTableCellString($list["cn"]) );
				$row->addCell( new odsTableCellString($list["uid"]) );				
				$table->addRow($row);
			}
		
		$ods->addTable($table);

		$ods->downloadOdsFile("ListadoAlumnos.ods");
	}
?>
	<html>
	<head>
		<title>Generador de etiquetas - Centros Educativos de Extremadura</title>
		<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
		<script type="text/JavaScript" language="javascript" src="jquery/jquery-1.4.2.min.js"></script>
		<link rel="stylesheet" type="text/css" media="screen" href="jquery/ui/css/ui-lightness/jquery-ui-1.8.5.custom.css" />
		<script type="text/JavaScript" language="javascript" src="jquery/ui/jquery-ui-1.8.5.custom.min.js"></script>
		
		<script languaje="javascript">

		function cargaSelectOffset () {
			var select = document.getElementById("offset");
			for (i=1;i<=23;i++) 
				select.options[select.length] = new Option(i, i);
                }


		$(function() {
			$("#sliderHeight").slider({
				value:70,
				min: 30,
				max: 150,
				step: 1,
				slide: function(event, ui) {
					$("#height").val(ui.value);
				}
			});
			$("#height").val($("#sliderHeight").slider("value"));

			$("#sliderWidth").slider({
				value:37,
				min: 10,
				max: 100,
				step: 1,
				slide: function(event, ui) {
					$("#width").val(ui.value);
				}
			});
			$("#width").val($("#sliderWidth").slider("value"));


			$("#sliderRows").slider({
				value:8,
				min: 1,
				max: 20,
				step: 1,
				slide: function(event, ui) {
					$("#rows").val(ui.value);
				}
			});
			$("#rows").val($("#sliderRows").slider("value"));

			$("#sliderColumns").slider({
				value:3,
				min: 1,
				max: 10,
				step: 1,
				slide: function(event, ui) {
					$("#columns").val(ui.value);
				}
			});
			$("#columns").val($("#sliderColumns").slider("value"));

			$("#sliderxMargin").slider({
				value:5,
				min: 1,
				max: 40,
				step: 1,
				slide: function(event, ui) {
					$("#xMargin").val(ui.value);
				}
			});
			$("#xMargin").val($("#sliderxMargin").slider("value"));

			$("#slideryMargin").slider({
				value:2,
				min: 1,
				max: 40,
				step: 1,
				slide: function(event, ui) {
					$("#yMargin").val(ui.value);
				}
			});
			$("#yMargin").val($("#slideryMargin").slider("value"));

			$("#sliderphotoWidth").slider({
				value:21,
				min: 10,
				max: 40,
				step: 1,
				slide: function(event, ui) {
					$("#photoWidth").val(ui.value);
				}
			});
			$("#photoWidth").val($("#sliderphotoWidth").slider("value"));

			$("#sliderphotoHeight").slider({
				value:25,
				min: 10,
				max: 40,
				step: 1,
				slide: function(event, ui) {
					$("#photoHeight").val(ui.value);
				}
			});
			$("#photoHeight").val($("#sliderphotoHeight").slider("value"));

			$("#sliderqrSize").slider({
				value:27,
				min: 10,
				max: 40,
				step: 1,
				slide: function(event, ui) {
					$("#qrSize").val(ui.value);
				}
			});
			$("#qrSize").val($("#sliderqrSize").slider("value"));

			$( "buttonLabels, input:submit, a", "#buttonLabels" ).button();
			$( "buttonGroups, input:button, a", "#buttonGroups" ).button();

			$(".ui-state-error").hide();
			$("#buttonLabels").hide();
		})

		function error(msn){
			$("#messageGetGroups").html(msn);
			$(".ui-state-error").show("slide",{},500);
		}

		function getGroups(){
			$.getJSON("backend.php?hostLDAP="+$("#hostLDAP").val()+"&userLDAP="+$("#userLDAP").val()+"&password="+$("#password").val(),
				function(data){		
					switch(data.msg){
						case 'noConnect':{ error("No se pudo conectar con el Host"); break;	}
						case 'noProtocol':{ error("Falló la configuracion de protocolo"); break; }
						case 'noPassword':{ error("Password incorrecto"); break; }
						case 'noGroups':{ error("No se han encontrado grupos"); break; }
						default:{
							$(".ui-state-error").hide();
							$("#buttonLabels").show();
							$("#buttonGroups").empty();
							$("#buttonGroups").append("Grupos<br><select multiple size='10' name='classroomGroup[]' id='classroomGroup'></select>");
							$.each(data.groups, function(i,item){
								$("#classroomGroup").append("<option value='"+item+"'>"+item+"</option>");
							});
						}
					}
				});
		}
		</script>
	</head>
	<body onload="cargaSelectOffset()">
	<h1 style="text-align:center;">Generador de etiquetas - Centros Educativos de Extremadura</h1>
	<form id='form_labels' method='POST' action="index.php">
		<table  style="width:100%;">
		<tr>
			<td colspan="2" style="width:25%; vertical-align:top;">
				<fieldset><legend>LDAP</legend>
					<div style="padding-left:10px">
						<p>Servidor LDAP:<br>
							<input type='text' id='hostLDAP' name='hostLDAP' class="text ui-widget-content ui-corner-all" value=''>
						</p>
						<p>Usuario:<br>
							<input type='text' id='userLDAP' name='userLDAP' class="text ui-widget-content ui-corner-all" value='admin'>
						</p>
						<p>Contrase&ntilde;a:<br>
							<input type='password' id='password' name='password' class="text ui-widget-content ui-corner-all">
						</p>
						<div id="buttonGroups"><input type="button" value="Obtener grupos" onClick="getGroups();"></div>
						<br>
						<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
							<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
							<strong id="messageGetGroups"></strong></p>
						</div>
					</div>
				</fieldset>
			</td>
			<td style="width:42%; vertical-align:top;">
				<fieldset><legend>Par&aacute;metros etiquetas</legend>
					<div style="padding-left:10px">
						<p>
							<label for="height">Ancho Etiqueta:</label>
							<input type="text" id="height" name="height" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderHeight" style="width:300px;"></div>
						</p>
						<p>
							<label for="width">Alto Etiqueta:</label>
							<input type="text" id="width" name="width" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderWidth" style="width:300px;"></div>
						</p>
						<p>
							<label for="rows">Filas:</label>
							<input type="text" id="rows" name="rows" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderRows" style="width:300px;"></div>
						</p>
						<p>
							<label for="columns">Columnas:</label>
							<input type="text" id="columns" name="columns" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderColumns" style="width:300px;"></div>
						</p>
						<p>
							<label for="xMargin">Margen X:</label>
							<input type="text" id="xMargin" name="xMargin" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderxMargin" style="width:300px;"></div>
						</p>
						<p>
							<label for="yMargin">Margen Y:</label>
							<input type="text" id="yMargin" name="yMargin" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="slideryMargin" style="width:300px;"></div>
						</p>
						<p>
							<label for="photoSize">Tama&ntilde;o Foto (Ancho):</label>
							<input type="text" id="photoWidth" name="photoWidth" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderphotoWidth" style="width:300px;"></div>
						</p>
						<p>
							<label for="photoSize">Tama&ntilde;o Foto (Alto):</label>
							<input type="text" id="photoHeight" name="photoHeight" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderphotoHeight" style="width:300px;"></div>
						</p>
						<p>
							<label for="qrSize">Tama&ntilde;o QR-Code:</label>
							<input type="text" id="qrSize" name="qrSize" style="border:0; color:#f6931f; font-weight:bold; width:30px; text-align:center;" />
							<div id="sliderqrSize" style="width:300px;"></div>
						</p>
						<p>
							<label for="filter">Filtro:</label><br>
							<input type="text" id="filter" name="filter" class="text ui-widget-content ui-corner-all"/>&nbsp;Valores separados por comas <b>(p.e. jprado, agordillo)</b>
						</p>
						<p>
							<label for="offset">Empezar en la posici&oacute;n</label><br>
							<select id="offset" name="offset">
							</select> 
						</p>
						<p style="text-align:center; font-weight:bold;">Par&aacute;metros por defecto para etiquetas apli24</p>
					</div>
				</fieldset>
			</td>
			<td style="width:33%; vertical-align:top;">
				<fieldset><legend>Datos QR-Code (opcional)&nbsp;&nbsp;&nbsp;<a href="http://es.wikipedia.org/wiki/C%C3%B3digo_QR" target="_blank">Info Wikipedia</a></legend>
					<div style="padding-left:10px">
						<p>Organizaci&oacute;n:<br>
							<input type="text" id="org" name="org" class="text ui-widget-content ui-corner-all"/>&nbsp;(p.e. IES Sta Eulalia)
						</p>
						<p>Direcci&oacute;n<br>
							<input type="text" id="address" name="address" class="text ui-widget-content ui-corner-all"/>&nbsp;
						</p>
						<p>Tel&eacute;fono<br>
							<input type="text" id="phone" name="phone" class="text ui-widget-content ui-corner-all"/>&nbsp;
						</p>
						<p>Email<br>
							<input type="text" id="email" name="email" class="text ui-widget-content ui-corner-all"/>&nbsp;
						</p>
						<p>Web<br>
							http://<input type="text" id="web" name="web" class="text ui-widget-content ui-corner-all"/>&nbsp;
						</p>
						<p style="text-align:center; font-weight:bold;">Se incluir&aacute; adem&aacute;s el Nombre, Apellidos y NIE del alumno.</p>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan="4" style="text-align:center;"><p>Tipo&nbsp;&nbsp;
				<select id="action" name="action">
					<option value="generate_labels">Etiquetas</option>
					<option value="generate_spreadsheet">Hoja de C&aacute;lculo</option>
				</select>
				</p>
			</td>
		</tr>
		<tr>
			<td colspan="4" style="text-align:center;"><br><div id="buttonLabels"><input type="submit" value="Generar"></div></td>
		</tr>
		</table>
	</form>
	</body>
	</html>
