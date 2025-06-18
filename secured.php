<?php 

// Decode base64
$json_order_params = base64_decode(rawurldecode( $_GET[ 'order' ] ));

// Decode json
$arr_order_params = json_decode($json_order_params);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Axaipay Payment Gateway</title>
    <script language="Javascript">
        var test = '<?= $arr_order_params->u ?>';
        function OnLoadEvent() { document.form.submit(); }
    </script>
</head>
<body OnLoad="OnLoadEvent();">
    <form action="<?= $arr_order_params->u ?>" method="post" id="paymentForm">

        <input type=hidden name="customerEmail" value="<?= $arr_order_params->e ?>" />
        <input type=hidden name="customerName" value="<?= $arr_order_params->n ?>" />
        <input type=hidden name="orderDescription" value="<?= $arr_order_params->d ?>" />
        <input type=hidden name="txnAmount" value="<?= $arr_order_params->a ?>" />
        <input type=hidden name="mchtId" value="<?= $arr_order_params->m ?>"/>
        <input type=hidden name="mchtTxnId" value="<?= $arr_order_params->i ?>"/>
        <input type=hidden name="redirectUrl" value="<?= $arr_order_params->r ?>"/>
        <input type=hidden name="backendUrl" value="<?= $arr_order_params->b ?>"/>
        <input type=hidden name="cancelUrl" value="<?= $arr_order_params->f ?>"/>
        <input type=hidden name="channel" value="<?= $arr_order_params->c ?>"/>
        <input type=hidden name="woocommerce" value="3.0"/>
        <input type=hidden name="signature" value="<?= $arr_order_params->s ?>"/>

        <noscript>
            <br> <br>
            <center>
                <h1>Processing payment</h1>
                <h2>
                    JavaScript is currently disabled or is not supported by your browser.<br>
                </h2>

                <h3>Please click Submit to continue the processing of your paymentresult</h3>
                <input type="submit" value="Submit">
            </center>
        </noscript>
    </form>

    <script type="text/javascript">
        document.getElementById("paymentForm").submit(); // SUBMIT FORM
    </script>

    </body>
</html>