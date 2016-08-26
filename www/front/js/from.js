
function Cname()
{
    var Name=document.getElementById("Tname").value;
    for(var i=0;i<Name.length;i++)
    {
    if(Name.charAt(i)>="0" && Name.charAt(i)<="9")
    {
    document.getElementById("Rname").style.color="red";
    document.getElementById("Rname").innerHTML=" 用户名不能有数字！";
    return false;
    }
    }
    if(Name.length=="")
    {
    document.getElementById("Rname").style.color="red";
    document.getElementById("Rname").innerHTML=" 请输入您的用户名！";
    return false;
    }
    else if(Name.length<6 || Name.length>16)
    {
    document.getElementById("Rname").style.color="red";
    document.getElementById("Rname").innerHTML=" 用户名只能由6-16位字符组成";
    return false;
    }
    else
    {
    document.getElementById("Rname").style.color="green";
    document.getElementById("Rname").innerHTML=" 该用户名可以使用";
    return true;
    }
    }
function Cpwd1()
{
    var pwd1=document.getElementById("Tpwd1").value;
    if(pwd1.length=="")
    {
    document.getElementById("Rpwd1").style.color="red";
    document.getElementById("Rpwd1").innerHTML=" 请输入密码！";
    return false;
    }
    else if(pwd1.length<6 || pwd1.length>16)
    {
    document.getElementById("Rpwd1").style.color="red";
    document.getElementById("Rpwd1").innerHTML=" 密码只能由6-16位字符组成";
    return false;
    }
    else
    {
    document.getElementById("Rpwd1").style.color="green";
    document.getElementById("Rpwd1").innerHTML=" 密码通过";
    return true;
    }
    }
function Cpwd2()
{
    var pwd1=document.getElementById("Tpwd1").value;
    var pwd2=document.getElementById("Tpwd2").value;
    if(pwd2!=pwd1)
    {
    document.getElementById("Rpwd2").style.color="red";
    document.getElementById("Rpwd2").innerHTML=" 请保持和上面密码的一致！";
    return false;
    }
    else
    {
    document.getElementById("Rpwd2").style.color="green";
    document.getElementById("Rpwd2").innerHTML=" 验证密码通过";
    return true;
    }
    }
function Cid()
{
    var pid=document.getElementById("Tid").value;
    if(pid.length=="")
    {
    document.getElementById("Rid").style.color="red";
    document.getElementById("Rid").innerHTML=" 请输入您的身份证号！";
    return false;
    }
    else if(pid.length<0 || pid.length>18)
    {
    document.getElementById("Rid").style.color="red";
    document.getElementById("Rid").innerHTML=" 现在只支持第二代18位身份证";
    return false;
    }
    else
    {
    document.getElementById("Rid").style.color="green";
    document.getElementById("Rid").innerHTML="  确认";
    return true;
    }
    }
function Cemail()
{
    var email=document.getElementById("Temail").value;
    if(email.length=="")
    {
    document.getElementById("Remail").style.color="red";
    document.getElementById("Remail").innerHTML=" 请输入您的电子邮箱地址！";
    return false;
    }
    else if(email.indexOf("@")<1 || email.indexOf(".")<2 || email.indexOf(".")<email.indexOf("@"))
    {
    document.getElementById("Remail").style.color="red";
    document.getElementById("Remail").innerHTML=" 请提供合法有效的电子邮箱地址";
    return false;
    }
    else
    {
    document.getElementById("Remail").style.color="green";
    document.getElementById("Remail").innerHTML="  格式合法";
    return true;
    }
    }
function Ctel()
{
    var Tel=document.getElementById("Ttel").value;
    for(var i=0;i<Tel.length;i++)
    {
    if(Tel.charAt(i)<"0" || Tel.charAt(i)>"9")
    {
    document.getElementById("Rtel").style.color="red";
    document.getElementById("Rtel").innerHTML=" 手机号码只能是11位数字！";
    return false;
    }
    }
    if(Tel.length=="")
    {
    document.getElementById("Rtel").style.color="red";
    document.getElementById("Rtel").innerHTML=" 请输入您的手机号码";
    return false;
    }
    else if(Tel.length<0 || Tel.length>11)
    {
    document.getElementById("Rtel").style.color="red";
    document.getElementById("Rtel").innerHTML=" 手机号码只有11位，且只能是数字";
    return false;
    }
    else
    {
    document.getElementById("Rtel").style.color="green";
    document.getElementById("Rtel").innerHTML=" 格式合法";
    return true;
    }
    }
var Team;  //变量Team，用于接收复选框 ‘全选’框上传的ID值
var IDname;  //用于接收复选框 各‘全选’项的子项的ID值
function CheckBox(Team,IDname) //函数(传参一，传参二)
{
    var ALT; //确定 alt属性的值
    var ID;  //获取变量Team 的值
    var IDname; //获取相应div的id名
    if(Team=="interest")
    {
    ALT="like";
    ID="interest";
    IDname="Rinter";
    }
    else if(Team=="technique")
    {
    ALT="tech";
    ID="technique";
    IDname="Rtech";
    }
    else
    {
    alert("传值出错！请联系程序员！");
    }
    var Call=document.getElementById(ID).checked;
    var bol=false;
    if(Call)
    {
    for(var i=0;i<document.Regform.elements.length;i++)
    {
    if(document.Regform.elements[i].type=="checkbox" && document.Regform.elements[i].alt==ALT)
    {
    document.Regform.elements[i].checked=true;
    document.getElementById(IDname).style.color="green";
    document.getElementById(IDname).innerHTML="√";
    }
    }
    }
    else
    {
    for(var i=0;i<document.Regform.elements.length;i++)
    {
    if(document.Regform.elements[i].type=="checkbox" && document.Regform.elements[i].alt==ALT)
    {
    document.Regform.elements[i].checked=false;
    document.getElementById(IDname).style.color="red";
    document.getElementById(IDname).innerHTML="× 请确认您选择了一项！";
    }
    }
    }
    }
function Cinterest()
{
    var bol=false;
    for(var i=0;i<document.Regform.elements.length;i++)
    {
    if(document.Regform.elements[i].type=="checkbox" && document.Regform.elements[i].alt=="like" && document.Regform.elements[i].checked)
    {
    document.getElementById("Rinter").style.color="green";
    document.getElementById("Rinter").innerHTML="√ 通过";
    bol=true;
    return true;
    }
    else
    {
    document.getElementById("Rinter").style.color="red";
    document.getElementById("Rinter").innerHTML=" 您没有进行选择";
    bol=false;
    }
    }
    if(bol==false)
    { return false;}
    }
function Ctechnique()
{
    var bol=false;
    for(var i=0;i<document.Regform.elements.length;i++)
    {
    if(document.Regform.elements[i].type=="checkbox" && document.Regform.elements[i].alt=="tech" && document.Regform.elements[i].checked)
    {
    document.getElementById("Rtech").style.color="green";
    document.getElementById("Rtech").innerHTML="√ 通过";
    bol=true;
    return true;
    }
    else
    {
    document.getElementById("Rtech").style.color="red";
    document.getElementById("Rtech").innerHTML=" 您没有进行选择";
    bol=false;
    }
    }
    if(bol==false)
    { return false;}
    }

function Result()
{
    if(Cname() && Cpwd1() && Cpwd2() && Cid() && Cemail() && Ctel() && Cinterest() && Ctechnique())
    {
    alert("您的资料已正确填写！请等待审查！");
    }
    else
    {
    alert("您的资料填写错误");
    }
    }