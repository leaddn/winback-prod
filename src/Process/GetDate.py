import pandas as pd
import re
import datetime
from mysql.connector import Error
import os

class GetDate():
    
    def __init__(self, connection):
        self.connection = connection

    def connectFrom(self, db_name, db_table, dbConnection_a):
        """Connect from db table to get dataframe

        Args:
            db_name (string): database name
            db_table (_type_): database table

        Returns:
            dataframe: selected records from db_table
        """
        sql_req = "select * from "+db_name+"."+db_table
        winback_sn = pd.read_sql(sql_req, dbConnection_a)
        return winback_sn


    def insertDate(self, date, where = ""):
        req = "UPDATE `sn` SET `creation_date`='"+date+"'"
        if where!='':
            req += " WHERE "+where
        return req

    def setDate(self, db_table, date, connection):
        '''
        Set creation_date in winback.sn IF sn already exists
        '''
        whereCond = "`SN` = '"+db_table+"'"
        req = self.insertDate(date, whereCond)
        #print(req)
        cursor = connection.cursor()
        try:
            cursor.execute(req)
            connection.commit()
            #print("Query successful")
        except Error as err:
            print(f"Error: '{err}'")

    # WIN & WGE
    def getWin(self, winback_sn):
        snToDateArray = []
        for sn in winback_sn['SN']:
            year = '20'
            if sn.startswith('WIN') or sn.startswith('WGE'):
                if "TEST" in sn or "RD" in sn or "USABILITY" in sn or "DEMO" in sn or "INDUS" in sn:
                    continue
                elif "TX" in sn:
                    year += str(sn[11:13])
                    month = sn[13:15]
                    date = str(year)+"-"+str(month)
                    snToDate = [sn, date]
                    snToDateArray.append(snToDate)
                elif "-" in sn:
                    sep_index = sn.find("-")
                    month_start = sep_index-2
                    year_start = month_start-2
                    date = ""
                    if re.match(r"\d{2}", sn[year_start:month_start]):
                        year += str(sn[year_start:month_start])
                        #print(year)
                        date += str(year)
                    if re.match(r"\d{2}", sn[month_start:sep_index]):
                        month = sn[month_start:sep_index]
                        #identify week number
                        if int(month) <= 12:
                            date += "-"+str(month)
                            snToDate = [sn, date]
                            snToDateArray.append(snToDate)
                            #print(date)
                        else:
                            year = int(year)
                            week = int(month)
                            date_modified = str(datetime.date.fromisocalendar(year, week, 1))            # generating the datetime
                            snToDate = [sn, date_modified]
                            snToDateArray.append(snToDate)
                elif len(sn) == 20 or len(sn) == 18 or len(sn) == 17 or len(sn) == 16:
                    year += str(sn[9:11])
                    month = sn[11:13]
                    date = str(year)+"-"+str(month)
                    snToDate = [sn, date]
                    snToDateArray.append(snToDate)
                else:
                    print(sn)
                    print(len(sn))
        return snToDateArray

    # DY
    def getDy(self, winback_sn):
        """_summary_

        Args:
            winback_sn (dataframe): _description_

        Returns:
            array: array of sn with their corresponding dates
        """
        snToDateArray = []

        for sn in winback_sn['SN']:
            year = '20'
            if sn.startswith('DY'):
                if "-" in sn:
                    sep_index = sn.find("-")
                    if re.match(r"[A-Z]", sn[sep_index+1]):
                        # check if letter just after -
                        year_start = sep_index+2
                        month_start = year_start+2
                        month_stop = month_start+1
                        date = ""
                        if re.match(r"\d{2}", sn[year_start:month_start]):
                            year += str(sn[year_start:month_start])
                            date += str(year)

                            if sn.startswith("DY101"):
                                snToDate = [sn, date]
                                snToDateArray.append(snToDate)
                        if re.match(r"\d", sn[month_start:month_stop]):
                            month = sn[month_start:month_stop]
                            if int(month) != 0:
                                if int(month) < 10:
                                    date += "-"+"0"+str(month)
                                else:
                                    date += "-"+str(month)
                                snToDate = [sn, date]
                                snToDateArray.append(snToDate)
                            else:
                                snToDate = [sn, date]
                                snToDateArray.append(snToDate)
                    else:
                        year_start = sep_index+1
                        month_start = year_start+2
                        month_stop = month_start+1
                        date = ""
                        if re.match(r"\d{2}", sn[year_start:month_start]):
                            year += str(sn[year_start:month_start])
                            date += str(year)
                        if re.match(r"\d", sn[month_start:month_stop]):
                            month = sn[month_start:month_stop]
                            if int(month) != 0:
                                if int(month) < 10:
                                    date += "-"+"0"+str(month)
                                else:
                                    date += "-"+str(month)
                                snToDate = [sn, date]
                                snToDateArray.append(snToDate)
                            else:
                                snToDate = [sn, date]
                                snToDateArray.append(snToDate)
                        else:
                            # if month is a letter, just keep the year
                            snToDate = [sn, date]
                            snToDateArray.append(snToDate)
            return snToDateArray
    def main(self, connection): 
        DB_TABLE = "sn"
        host = os.getenv('DB_HOSTNAME')
        admin = os.getenv('DB_ADMIN')
        pwd = os.getenv('DB_PWD')
        db = os.getenv('DB_NAME')
        
        #connection = create_db_connection(host, admin, pwd, db)
        winback_sn = self.connectFrom(db, DB_TABLE, connection)
        winToDateArray = self.getWin(winback_sn)
        for v in range(len(winToDateArray)):
            self.setDate(winToDateArray[v][0], winToDateArray[v][1], connection)

        dyToDateArray = self.getDy(winback_sn)
        for v in range(len(dyToDateArray)):
            self.setDate(dyToDateArray[v][0], dyToDateArray[v][1], connection)

if __name__ == "__main__":
    getDate = GetDate()
    getDate.main(getDate.connection)