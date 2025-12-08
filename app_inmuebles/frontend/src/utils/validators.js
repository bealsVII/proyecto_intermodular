export function isPositiveNumber(val){
  return !isNaN(Number(val)) && Number(val) > 0
}