{{-- تشخیص چهره سمت مرورگر با face-api.js --}}
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
window.AttFace = (function () {
  var MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/model';
  var ready = null;

  function load() {
    if (ready) return ready;
    ready = Promise.resolve()
      .then(function () {
        if (!window.faceapi) throw new Error('کتابخانه تشخیص چهره لود نشد');
        return faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
      })
      .catch(function (err) {
        ready = null;
        throw err;
      });
    return ready;
  }

  function detectFromFile(file) {
    return load().then(function () {
      return new Promise(function (resolve, reject) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
          faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
            .then(function (faces) {
              URL.revokeObjectURL(url);
              resolve({ ok: faces && faces.length > 0, count: faces ? faces.length : 0 });
            })
            .catch(function (e) {
              URL.revokeObjectURL(url);
              reject(e);
            });
        };
        img.onerror = function () {
          URL.revokeObjectURL(url);
          reject(new Error('خواندن تصویر ناموفق'));
        };
        img.src = url;
      });
    });
  }

  function detectFromVideo(videoEl) {
    return load().then(function () {
      return faceapi.detectAllFaces(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
        .then(function (faces) {
          return { ok: faces && faces.length > 0, count: faces ? faces.length : 0 };
        });
    });
  }

  function captureVideoBlob(videoEl) {
    var canvas = document.createElement('canvas');
    canvas.width = videoEl.videoWidth || 640;
    canvas.height = videoEl.videoHeight || 480;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
    return new Promise(function (resolve) {
      canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.92);
    });
  }

  return { load: load, detectFromFile: detectFromFile, detectFromVideo: detectFromVideo, captureVideoBlob: captureVideoBlob };
})();
</script>
