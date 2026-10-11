document.addEventListener('DOMContentLoaded',function(){
  const radios=[...document.querySelectorAll('input[name="origen"]')];
  const internacional=document.getElementById('bloqueLigaInternacional');
  const nacional=document.getElementById('bloqueLigaNacional');
  const selectInt=document.getElementById('in_liga');
  const selectNac=document.getElementById('ligaNacional');
  const otra=document.getElementById('bloqueLigaOtra');
  const inputOtra=document.getElementById('ligaOtra');
  if(!radios.length||!selectInt||!selectNac||!inputOtra)return;
  function actualizar(){
    const esNacional=radios.find(r=>r.checked)?.value==='nacional';
    internacional.hidden=esNacional;
    nacional.hidden=!esNacional;
    selectInt.disabled=esNacional;
    selectInt.required=!esNacional;
    selectNac.disabled=!esNacional;
    selectNac.required=esNacional;
    const esOtra=esNacional&&selectNac.value==='__otra__';
    otra.hidden=!esOtra;
    inputOtra.disabled=!esOtra;
    inputOtra.required=esOtra;
    document.dispatchEvent(new Event('goolpass-liga-cambiada'));
  }
  radios.forEach(r=>r.addEventListener('change',actualizar));
  [selectInt,selectNac,inputOtra].forEach(c=>c.addEventListener('input',actualizar));
  actualizar();
});
function goolpassLigaSeleccionada(){
 const n=document.querySelector('input[name="origen"]:checked')?.value==='nacional';
 if(!n)return document.getElementById('in_liga')?.value||'';
 const v=document.getElementById('ligaNacional')?.value||'';
 return v==='__otra__'?(document.getElementById('ligaOtra')?.value||''):v;
}
