<?php
declare(strict_types=1);

final class CsvImporter {
 public function rows(string $path): Generator {
  $h=fopen($path,'rb'); if(!$h) throw new RuntimeException('CSV قابل خواندن نیست.');
  $headers=fgetcsv($h,0,","); if(!$headers) throw new RuntimeException('هدر CSV یافت نشد.');
  $headers=array_map(fn($x)=>trim((string)$x),$headers);
  while(($row=fgetcsv($h,0,","))!==false){
   if(count($row)===1 && trim((string)$row[0])==='') continue;
   $row=array_pad($row,count($headers),'');
   yield array_combine($headers,array_slice($row,0,count($headers)));
  }
  fclose($h);
 }
 public function split(string $value): array {
  $value=trim($value); if($value==='') return [];
  return array_values(array_filter(array_map('trim',preg_split('/\s*\|\s*/u',$value) ?: []),fn($v)=>$v!==''));
 }
 public function disks(array $r): array {
  $models=$this->split((string)($r['DiskModels']??''));
  $sizes=$this->split((string)($r['DiskSizesGB']??''));
  $serials=$this->split((string)($r['DiskSerials']??''));
  $interfaces=$this->split((string)($r['DiskInterfaces']??''));
  $n=max(count($models),count($sizes),count($serials),count($interfaces)); $out=[];
  for($i=0;$i<$n;$i++) $out[]=['index'=>$i+1,'model'=>$models[$i]??null,'size_gb'=>$sizes[$i]??null,'serial_no'=>$serials[$i]??null,'interface'=>$interfaces[$i]??null];
  return $out;
 }
 public function ramSlots(array $r): array {
  $details=$this->split((string)($r['RAMSlotDetails']??'')); $out=[];
  foreach($details as $i=>$text){
   $item=['slot_no'=>$i+1,'raw'=>$text];
   foreach(['capacity_gb'=>'/([0-9]+(?:\.[0-9]+)?)\s*GB/i','speed_mhz'=>'/Speed\s*:\s*(\d+)\s*MHz/i','ram_type'=>'/Type\s*:\s*([^\/]+)/i','manufacturer'=>'/Manufacturer\s*:\s*([^\/]+)/i','part_number'=>'/PartNumber\s*:\s*([^\/]+)/i','serial_no'=>'/Serial\s*:\s*([^,\/]+)/i'] as $k=>$rx) if(preg_match($rx,$text,$m)) $item[$k]=trim($m[1]);
   $item['state']='occupied'; $out[]=$item;
  }
  return $out;
 }
}
