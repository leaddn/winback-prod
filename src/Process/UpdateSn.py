import mysql.connector
from mysql.connector import Error
import pandas as pd
import gspread as gs
import sys
from ConnectDb import create_db_connection
from GetDate import GetDate

class UpdateSn():
    
    def importData(self, filename, month, pathToJson):
        """PRODUCT LIST COPY FILE

        Args:
            filename (_type_): _description_
            month (_type_): _description_

        Returns:
            _type_: _description_
        """
        gc = gs.service_account(pathToJson)
        sh = gc.open(filename)
        ws = sh.worksheet(month)
        df_academy = pd.DataFrame(ws.get_all_records())
        df_academy = df_academy.rename(columns={"DEVICE NAME": "DEVICE", "SERIAL NO.": "SN"})

        df_academy['DEVICE'] = df_academy['DEVICE'].str.upper()
        df_academy['SN'] = df_academy['SN'].str.upper()
        df_academy['COUNTRY'] = df_academy['COUNTRY'].str.upper()
        df_academy['SUBTYPE'] = ''
        df_academy['DEVICE'][df_academy['DEVICE']=="NEO LIFT"] = "NEOLIFT"
        # subtype rshock
        df_academy['SUBTYPE'][df_academy['DEVICE']=="NEOLIFT"] = "RSHOCK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="NEOLIFT_V2"] = "RSHOCK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BIOBACK"] = "RSHOCK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="RSHOCK"] = "RSHOCK"
        # subtype back daeyang
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK1S"] = "BACK DAEYANG"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3"] = "BACK DAEYANG"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3SE"] = "BACK DAEYANG"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3SE COLOR"] = "BACK DAEYANG"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3SE PRO"] = "BACK DAEYANG"
        # subtype back winback
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3TE"] = "BACK WINBACK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK3TX"] = "BACK WINBACK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="BACK4"] = "BACK WINBACK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="E-MAGNESCENCE"] = "BACK WINBACK"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="NEOCARE ELITE"] = "BACK WINBACK"
        # subtype cryo
        df_academy['SUBTYPE'][df_academy['DEVICE']=="CRYOBACK3"] = "CRYO"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="CROBACK4"] = "CRYO"
        df_academy['SUBTYPE'][df_academy['DEVICE']=="WINSHOCK"] = "CRYO"
        # subtype presso
        df_academy['SUBTYPE'][df_academy['DEVICE']=="GMOVE SUIT"] = "PRESSO"
        products_list = df_academy.values.tolist()
        return products_list
    
    def execute_list_query(self, connection, sql, val):
        """INSERTION

        Args:
            connection (_type_): _description_
            sql (_type_): _description_
            val (_type_): _description_
        """
        cursor = connection.cursor()
        try:
            cursor.executemany(sql, val)
            connection.commit()
            print("Query successful")
        except Error as err:
            print(f"Error: '{err}'")
    
    def main(self, filename, month, pathToJson, connection):
        products_list = self.importData(filename, month, pathToJson)
        sql = '''
        INSERT INTO sn (Device, SN, Date, country, subtype) 
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE  
        Device = VALUES(Device),
        Date = VALUES(Date),
        country = VALUES(country),
        subtype = VALUES(subtype);
        '''
        self.execute_list_query(connection, sql, products_list)

if __name__ == "__main__":
    updateSn = UpdateSn()
    filename = sys.argv[2]
    month = sys.argv[3]
    pathToJson = sys.argv[1]
    connection = create_db_connection()
    updateSn.main(filename, month, pathToJson, connection)
    getDate = GetDate(connection)
    getDate.main(connection)