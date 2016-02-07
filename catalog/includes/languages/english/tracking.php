<?php
/*

COUNTRY CODES

Argentina = AR
Austria = AT
Australia = AU
Belgium = BE
Brazil = BR
Canada = CA
Switzerland = CH
Chile = CL
China = CN
Colombia = CO
Costa Rica = CR
Germany = DE
Denmark = DK
Dominican Republic = DO
Spain = ES
Finland = FI
France = FR
United Kingdom = GB
Greece = GR
Guatemala = GT
Hong Kong = HK
Indonesia = ID
Ireland = IE
Israel = IL
India = IN
Italy = IT
Japan = JP
Korea (South) = KR
Mexico = MX
Malaysia = MY
Netherlands = NL
Norway = NO
New Zealand = NZ
Panama = PA
Peru = PE
Philippines = PH
Puerto Rico = PR
Portugal = PT
Russian Federation = RU
Sweden = SE
Singapore = SG
Thailand = TH
Taiwan = TW
United States = US
Venezuela = VE
Virgin Islands(U.S.) = VI
South Africa = ZA

LANGUAGE CODES

Danish = dan
Dutch = dut
English = eng
French = fre
German = ger
Italian = ita
Portuguese = por
Spanish = spa
*/

// ** Change These Variables To Match Your Site**
define('NAVBAR_TITLE', 'Tracking'); //Will appear in the navigation bar. Example: Top >> Catalog >> Tracking
define('HEADING_TITLE', 'Package Tracking'); //Will appear in bold at the top of the page.
define('HTML_ACCESS_KEY', 'YOUR_ACCESS_KEY'); // HTML access key issued to you by ups.com (http://www.ec.ups.com/ecommerce/gettools/gtools_intro.html).
define('HTML_VERSION', '3.0'); //Tracking HTML Version number as stated on UPS website or confirmation email from UPS.
define('INQUIRY_TYPE', 'T'); // T = By tracking number - R = By reference number. (Don't change unless you know what you are doing)
define('DEFAULT_LANGUAGE', 'eng'); //Default language to view tracking results in. (3 letter language code from above)
define('DEFAULT_COUNTRY', 'us'); // Default country packages will be shipped. (2 letter country code from above)

define('TEXT_INFORMATION', 'Enter your <b>UPS</b> tracking number below:');


define('TRACKING_FORM_UPS', '

<form method="get" action="http://wwwapps.ups.com/etracking/tracking.cgi" target="_blank">
<input type="text" size="35" name="InquiryNumber1"><br>
<input type="hidden" name="TypeOfInquiryNumber" value="' . INQUIRY_TYPE . '">
<input type="hidden" name="UPS_HTML_License" value="' . 'HTML_ACCESS_KEY' . '">
<input type="hidden" name="UPS_HTML_Version" Value="' . HTML_VERSION . '">
<input type="hidden" name="IATA" value="' . DEFAULT_COUNTRY . '">
<input type="hidden" name="Lang" value="' . DEFAULT_LANGUAGE . '">
<input type="submit" name="submit" value="Track Package">
<input type="reset" value="Clear">
</form>
');

define('TEXT_INFORMATION_FEDEX', 'Enter your <b>Fedex</b> tracking number below:');

define('TRACKING_FORM_FEDEX', '

<FORM NAME="tracking" ACTION="http://www.fedex.com/cgi-bin/tracking" target="_blank">
<input type="text" size="35" name="tracknumbers"><br>
                <INPUT TYPE="hidden" NAME="action" VALUE="track">
                <INPUT TYPE="hidden" NAME="language" VALUE="english">
                <INPUT TYPE="hidden" NAME="cntry_code" VALUE="us">
                <INPUT TYPE="hidden" NAME="mps" VALUE="y">
                <INPUT TYPE="submit" VALUE="Track Package">
				<INPUT TYPE="reset" value="Clear">
                </FORM>
');

define('TEXT_INFORMATION_USPS', 'Enter your <b>USPS</b> tracking number below:');

define('TRACKING_FORM_USPS', '

<FORM ACTION="http://trkcnfrm1.smi.usps.com/netdata-cgi/db2www/cbd_243.d2w/output" TARGET="new" METHOD="POST" name="getTrackNum" onSubmit="return getTrackNum_validator(this)"><INPUT TYPE=HIDDEN TABINDEX=5 NAME=CAMEFROM VALUE=OK>
<INPUT TYPE="TEXT" ID="Enter number from shipping receipt:" MAXLENGTH="34" SIZE="35" NAME="strOrigTrackNum"><br>
                <INPUT TYPE="submit" VALUE="Track Package">
				<INPUT TYPE="reset" value="Clear">
				</FORM>
');

?>