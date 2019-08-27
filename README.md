# sped-lumen

API-REST para emissão,cancelamento e impressão de NF-e(transferência) e NFC-e com base no framework [Lumen](https://lumen.laravel.com) e 
[sped-nfe](https://github.com/nfephp-org/sped-nfe).

## Importante
_A construção da api tem como intuito, centralizar a emissão dos documentos fiscais em um único lugar e sem dependência 
de dados dos clientes._

_Quanto aos certificados digitais dos clientes, eu fiz uma outra api que retorna o certificado em `base_64`._




**Install**

`composer install`

###### **Request de exemplo _POST_(/nfce)**

`{
     "empresa": {
         "codigo_ibge_estado": "28",
         "serie": "1",
         "numerodanota": "23",
         "datahoraemissao": "2019-07-05 17:34:13",
         "codigo_mun_ibge": "2800308",
         "dv_chave": "6",
         "razao_social": "Alimenta",
         "nomefantasia": "Alimenta",
         "inscricaoestadual": "12312312",
         "cnpj": "193812973129038",
         "endereco": "Av Nacoes Unidas",
         "numero": "370",
         "complemento": "",
         "bairro": "Nossa Senhora Do Carmo",
         "id_cidade": "2800308",
         "cidade": "Aracaju",
         "estado_uf": "SE",
         "cep": "49032010",
         "codigo_numerico": "00004996",
         "telefoneEmpresa": "7932222222",
         "csc": "1FB46224-B1DA-43BB-BAD7-289F6900C4CC",
         "id_csc": "000001",
         "tpAmb": 2,
         "certificado": "cnRfbm92YWZvb2Q="// utilizado para pegar o certificado digital
     },
     "tagPag": [
         {
             "tPag": "04",
             "vPag": "60.00"
         }
     ],
     "produtos": [
         {
             "id": "169",
             "quant": "1",
             "titulo": "MEIO FILE A BEIRA RIO",
             "ncm": "21069090",
             "cfop": "5102",
             "unidade": "UN",
             "valrUnid": "60",
             "valrDescontoItem": "0.00",
             "valrTotal": "60",
             "valrDescontoGeral": "0",
             "tagICMS": {
                 "orig": 0,
                 "cst": "102",
                 "pICMS": "1"
             },
             "tagPIS": {
                 "cst": 99,
                 "pPIS": 18
             },
             "tagCOFINS": {
                 "cst": 99,
                 "vBC": 0,
                 "pCOFINS": 0,
                 "qBCProd": 0,
                 "vCOFINS": 0
             },
             "cest": "0301500",
             "troco": "0.00"
         }
     ],
     "cliente": {
            "cpf":"000000000"
         },
     "obs": "teste de Obs"
     }
  `

###### **Response (/nfce)**

`{
     "chave": "28190830797552000133650010000000241195502292",
     "numlote": "24",
     "numrec": "283065073624274",
     "numprot": "328190000122879",
     "nnota": "24",
     "cstat": "100",
     "hash": "eyJjaGF2ZSI6IjI4MTkwODMwNzk3NTUyMDAwMTMzNjUwMDEwMDAwMDAwMjQxMTk1NTAyMjkyIiwibnVtbG90ZSI6IjI0IiwibnVtcmVjIjoiMjgzMDY1MDczNjI0Mjc0IiwibnVtcHJvdCI6IjMyODE5MDAwMDEyMjg3OSIsIm5ub3RhIjoiMjQiLCJjc3RhdCI6IjEwMCIsIm1vZGVsIjo2NSwibSI6IjA4IiwieSI6IjIwMTkiLCJmb2xkZXIiOiJydF9ub3ZhZm9vZCJ9",
     "xml_note": "XML-PROTOCOLADO"
 }`
 
 ###### **Impressão _GET_ (/print?hash=)**
 
`{URL}/print?hash=`

 ###### **Cancelamento _POST_ (/cancel)**
 
`{
     "empresa": {
         "codigo_ibge_estado": "28",
         "serie": "1",
         "numerodanota": "20",
         "datahoraemissao": "2019-07-05 17:34:13",
         "codigo_mun_ibge": "2800308",
         "dv_chave": "6",
         "razao_social": "Alimenta",
         "nomefantasia": "Alimenta",
         "inscricaoestadual": "123123123123",
         "cnpj": "12391293012",
         "endereco": "Av Nacoes Unidas",
         "numero": "370",
         "complemento": "",
         "bairro": "Nossa Senhora Do Carmo",
         "id_cidade": "2800308",
         "cidade": "Aracaju",
         "estado_uf": "SE",
         "cep": "49032010",
         "codigo_numerico": "00004996",
         "telefoneEmpresa": "7932222222",
         "csc": "1FB46224-B1DA-43BB-BAD7-289F6900C4CC",
         "id_csc": "000001",
         "tpAmb": 2,
         "certificado": "cnRfbm92YWZvb2Q="
     },
     "just": "Desistencia do comprador no momento da retirada",
     "hash": "eyJjaGF2ZSI6IjI4MTkwNzMwNzk3NTUyMDAwMTMzNjUwMDEwMDAwMDAwMjMxMDA3NjAyNTA1IiwibnVtbG90ZSI6IjIzIiwibnVtcmVjIjoiMjgzMDY1MDczMTk5NjE0IiwibnVtcHJvdCI6IjMyODE5MDAwMDEyMTY3MiIsIm5ub3RhIjoiMjMiLCJjc3RhdCI6IjEwMCIsIm1vZGVsIjo2NSwibSI6IjA3IiwieSI6IjIwMTkiLCJmb2xkZXIiOiJydF9ub3ZhZm9vZCJ9"
 }`

