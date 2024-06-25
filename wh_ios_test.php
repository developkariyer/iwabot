<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner</title>
    <style>
        video {
            width: 100%;
            height: auto;
        }
        #barcode {
            font-size: 1.5em;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<h1>Barcode Scanner</h1>
<video id="video" autoplay></video>
<p>Scanned Code: <span id="barcode">Waiting...</span></p>

<script src="https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.20/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js"></script>

<script>
    // WebAssembly polyfill for some browsers
    try { 
        window['BarcodeDetector'].getSupportedFormats(); 
    } catch { 
        window['BarcodeDetector'] = barcodeDetectorPolyfill.BarcodeDetectorPolyfill; 
    }

    async function initBarcodeScanner() {
        // Create a BarcodeDetector for simple retail operations.
        const barcodeDetector = new BarcodeDetector({ formats: ["ean_13", "ean_8", "upc_a", "upc_e"] });

        // Get a stream for the rear camera, else the front (or side?) camera.
        const video = document.querySelector('video');
        try {
            video.srcObject = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        } catch (error) {
            console.error('Error accessing the camera:', error);
            return;
        }

        // Let's scan barcodes forever
        while(true) {
            // Try to detect barcodes in the current video frame.
            const barcodes = await barcodeDetector.detect(video);

            // Continue loop if no barcode was found.
            if (barcodes.length == 0) {
                // Scan interval 50 ms like in other barcode scanner demos.
                // The higher the interval the longer the battery lasts.
                await new Promise(r => setTimeout(r, 50));
                continue;
            }

            // We expect a single barcode.
            // It's possible to compare X/Y coordinates to get the center-most one.
            // One can also do "preferred symbology" logic here.
            document.getElementById("barcode").innerText = barcodes[0].rawValue;
            
            // Notify user that a barcode has been found.
            navigator.vibrate(200);

            // Give the user time to find another product to scan
            await new Promise(r => setTimeout(r, 1000));
        }
    }

    document.addEventListener('DOMContentLoaded', initBarcodeScanner);
</script>

</body>
</html>
