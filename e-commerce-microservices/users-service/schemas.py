from pydantic import BaseModel

class UserRead(BaseModel):
    id: int
    name: str
    email: str
    mobile: int

    class Config:
        orm_mode = True