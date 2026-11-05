function toggleSidebar(){
  const sidebar=document.querySelector('.sidebar');
  const backdrop=document.getElementById('sidebarBackdrop');
  const isOpen=sidebar.classList.toggle('mobile-open');
  backdrop.classList.toggle('visible',isOpen);
}
function closeSidebar(){
  document.querySelector('.sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarBackdrop').classList.remove('visible');
}
function toast(message){
  const el=document.getElementById('toast');
  el.textContent=message;el.classList.add('show');
  clearTimeout(window.toastTimeout);
  window.toastTimeout=setTimeout(()=>el.classList.remove('show'),2500);
}

const searchInput=document.getElementById('search');
if(searchInput){
  searchInput.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(row=>{
      row.style.display=row.textContent.toLowerCase().includes(q)?'':'none';
    });
  });
}

(function(){
  const body=document.body,btn=document.getElementById('collapseBtn');
  if(!btn)return;
  function setCollapsed(v,save){
    body.classList.toggle('collapsed',v);
    btn.setAttribute('aria-expanded',String(!v));
    btn.title=v?'Expand sidebar':'Collapse sidebar';
    if(save){try{localStorage.setItem('og-sidebar',v?'1':'0')}catch(e){}}
  }
  let saved=null;try{saved=localStorage.getItem('og-sidebar')}catch(e){}
  setCollapsed(saved===null?window.innerWidth<=1000:saved==='1',false);
  btn.addEventListener('click',()=>setCollapsed(!body.classList.contains('collapsed'),true));
})();

const period=document.getElementById('period');
if(period){
  period.addEventListener('change',function(){
    toast('Chart filter selected: '+this.value+'. Sample chart shown.');
  });
}
function toggleNotif(e){
  e.stopPropagation();
  const m=document.getElementById('notifMenu');
  if(m)m.classList.toggle('open');
}
document.addEventListener('click',function(e){
  const m=document.getElementById('notifMenu');
  if(m&&!m.contains(e.target))m.classList.remove('open');
});