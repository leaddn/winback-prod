import mysql.connector
from mysql.connector import Error
import pandas as pd
import gspread as gs
import os
from dotenv import load_dotenv
import sys
from ConnectDb import create_db_connection
from GetDate import GetDate

class UpdateCode():
    
    def importData(self, filename):
        """PRODUCT LIST COPY FILE

        Returns:
            _type_: _description_
        """
        df = pd.read_excel(filename)
        sub_df = df[["ID produit enregistré", "ID compte"]]
        products_list = sub_df.values.tolist()
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
    
    def main(self, connection, filename):
        products_list = self.importData(filename)
        reverse_products_list = [[x[1], x[0]] for x in products_list]
        sql = '''
        UPDATE sn set `client_code` = %s
        where `SN` = %s
        '''
        self.execute_list_query(connection, sql, reverse_products_list)

if __name__ == "__main__":
    updateCode = UpdateCode()
    connection = create_db_connection()
    filename = sys.argv[1]
    updateCode.main(connection, filename)