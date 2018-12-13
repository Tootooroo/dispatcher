var release = document.querySelector(".release");
var submitButton = document.querySelector(".submit");
var GetBinButton = document.querySelector(".GetBin");
var isDone = 0;
var targetImgUrl;

release.focus();


function buildBin() {
    $.ajax({
        url:"imgGen.php",
        type:"POST",
        async:true,
        data:{release_data:release},
        dataType:"json",
        
        error:function() {
            alert("Error"); 
            isDone = 0;
            GetBinButton.disabled = true;
        },
        
        success:function(data) {
            isDone = 1;
            targetImgUrl = data.url; 
            GetBinButton.disabled = false;
        }
    })
}

submitButton.addEventListener('click', buildBin);

function getBin() {
    if (isDone == 1) 
        window.open(targetImgUrl, '_blank');         
}

GetBinButton.addEventListener('click', getBin);


