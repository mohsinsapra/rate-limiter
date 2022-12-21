$("#loginForm").submit(function (event) {
  event.preventDefault();
  var form_data = $("#loginForm").serialize();
  var $form = $(this);
  var $inputs = $form.find("input, button");
  $inputs.prop("disabled", true);

  loginRateLimiter(form_data);
});
var server_request;
function loginRateLimiter(form_data) {
  var browser_cookie = document.cookie;
  console.log("form submitted: " + form_data);

  // console.log(form_data + "&" + browser_cookie);

  if (server_request) {
    server_request.abort();
  }
  server_request = $.ajax({
    url: "server.php",
    type: "post",
    data: form_data + "&" + browser_cookie + "&loginRateLimiter=true",
  });
  server_request.done(function (response, textStatus, jqXHR) {
    console.log("Hooray, it worked!");
    alert(JSON.parse(response).message);
  });
  server_request.fail(function (jqXHR, textStatus, errorThrown) {
    console.log("The following error occurred: " + textStatus, errorThrown);
    // alert("The following error occurred: " + textStatus, errorThrown);
  });
  server_request.always(function (response) {
    response = response.responseJSON;
    alert(response.message);
    $("#loginForm").find("input, button").prop("disabled", false);
  });
}
