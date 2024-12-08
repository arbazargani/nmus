// JS
let close_btn = document.querySelectorAll(".response-close");
close_btn.forEach(function(a){
    a.addEventListener("click" , function(){
        let response = this.closest(".response");
        response.classList.toggle("show");
    });
});

// Alert Generation Function
function alert_gen(type , msg , title){
    // let alert = document.querySelector("."+type);
    // let alert_msg = document.querySelector("."+type+ ".response-text p").innerHTML;
    if(document.querySelector("."+type)){
        document.querySelector("."+type).classList.add("show");
        document.querySelector("."+type+" .response-text p").innerHTML = msg;
        if(title){
            document.querySelector("."+type+" .response-text h3").innerHTML = title;
        }
    }
}

function show_alert(){
    var a = document.querySelector(".response");
    a.classList.toggle("show");
}

function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

document.querySelector("#url_form").addEventListener("submit" , ()=>{
    event.preventDefault();
    document.querySelector(".overlay").classList.add("show");

    // Error Div
    document.querySelector(".error_wrap .error pre").innerHTML = "";
    document.querySelector(".error_wrap").classList.add("active");
    
    // Hiding all Notifs
    document.querySelector(".success").classList.remove("show");
    document.querySelector(".fail").classList.remove("show");
    document.querySelector(".info").classList.remove("show");
    
    let url = document.querySelector('input[name="url"]').value;
    let req = "/sender.php?url="+url;
    var xhttp = new XMLHttpRequest();
    xhttp.timeout = 80000;
    xhttp.onreadystatechange = function() {
        if(this.readyState == 0){
            // document.querySelector(".error_wrap .error pre").innerHTML += "<br>0";
        }
        if(this.readyState == 1){
            // document.querySelector(".error_wrap .error pre").innerHTML += "<br>1";
        }
        if(this.readyState == 2){
            // document.querySelector(".error_wrap .error pre").innerHTML += "<br>2";
        }
        if(this.readyState == 3){
            // document.querySelector(".error_wrap .error pre").innerHTML += "<br>3";
        }
        if(this.status == 403){
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>status: 403 Forbidden";
            document.querySelector(".overlay").classList.remove("show");
            alert_gen("fail" , "Ajax error: 403 Forbidden" , "ارور‌!");
        }
        if(this.status == 404){
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>status: 404 Page Not Found";
            document.querySelector(".overlay").classList.remove("show");
            alert_gen("fail" , "Ajax error: 404 Page Not Found" , "ارور‌!");
        }
        if(this.status == 500){
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>status: 500 Internal Server Error";
            document.querySelector(".overlay").classList.remove("show");
            alert_gen("fail" , "Ajax error: 500 Internal Server Error" , "ارور‌!");
        }
        if(this.status == 502){
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>status: 502 Bad Gateway";
            document.querySelector(".overlay").classList.remove("show");
            alert_gen("fail" , "Ajax error: 502 Bad Gateway" , "ارور‌!");
        }
        if(this.status == 504){
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>status: 504 Gateway Timeout";
            document.querySelector(".overlay").classList.remove("show");
            alert_gen("fail" , "Ajax error: 504 Gateway Timeout" , "ارور‌!");
        }
        if(this.readyState == 4 && this.status == 200){
            document.querySelector(".overlay").classList.remove("show");
        
            // if(isJson(this.responseText)){
            //     let myObj = JSON.parse(this.responseText);
            //     console.log(myObj["rk_push"]);
        
            //     if(myObj["rk_push"] == 1){
            //         alert_gen("success" , "پوش شما ارسال شد." , "موفق");
            //     }
            //     if(myObj["rk_push"] == 0){
            //         alert_gen("fail" , "متاسفانه ارسال پوش با خطا مواجه شد." , "ارور‌!");
            //         document.querySelector(".error_wrap .error pre").innerHTML += "<br>Response: " + this.response;
            //     }
        
            //     if(myObj["mg_error"]){
            //         alert_gen("fail" , myObj["mg_error"] , "ارور‌!");
            //         document.querySelector(".fail .response-text p").innerHTML = myObj["mg_error"];
            //         document.querySelector(".fail").classList.add("show");
            //     }

            //     console.log("sss:");
            //     console.table(myObj);
            // }
        
            document.querySelector("#url_form input[name=url]").value = "";
            document.querySelector(".error_wrap .error pre").innerHTML += "<br>Response: " + this.response;
        
            console.log(this.response);
            
        }
    }
    xhttp.ontimeout = (e) => {
        document.querySelector(".overlay").classList.remove("show");
        document.querySelector(".fail").classList.add("show");
    };
    xhttp.open("GET" , req , true);
    xhttp.send();
});
